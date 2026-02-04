<?php

/**
 * Copyright (c) 2025 Fexa AI
 *
 * All Rights Reserved.
 *
 * This module is proprietary software owned by Fexa AI. All intellectual property rights, including copyrights, trademarks, and trade secrets, are reserved by Fexa AI.
 */

namespace PrestaShop\Module\FexaAiConnector\Server;

use Psr\SimpleCache\CacheInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

class CustomFileCache implements CacheInterface
{
    public function __construct(
        private string $cacheFile,
        private int $filePermission = 0664,
        private int $dirPermission = 0775,
    ) {
        $this->ensureDirectoryExists(dirname($this->cacheFile));
    }

    public function get($key, $default = null)
    {
        $data = $this->readCacheFile();
        $key = $this->sanitizeKey($key);

        if (!isset($data[$key])) {
            return $default;
        }

        if ($this->isExpired($data[$key]['expiry'])) {
            $this->delete($key);

            return $default;
        }

        return $data[$key]['value'] ?? $default;
    }

    public function set($key, $value, $ttl = null)
    {
        $key = $this->sanitizeKey($key);

        $handle = @fopen($this->cacheFile, 'c+b');
        if ($handle === false) {
            return false;
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                return false;
            }

            $data = $this->readLockedCacheFile($handle);

            $data[$key] = [
                'value' => $value,
                'expiry' => $this->calculateExpiry($ttl),
            ];

            return $this->writeLockedCacheFile($handle, $data);
        } finally {
            if (is_resource($handle)) {
                flock($handle, LOCK_UN);
                fclose($handle);
            }
        }
    }

    public function delete($key)
    {
        $key = $this->sanitizeKey($key);

        $handle = @fopen($this->cacheFile, 'c+b');
        if ($handle === false) {
            return true;
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                return false;
            }

            $data = $this->readLockedCacheFile($handle);

            if (isset($data[$key])) {
                unset($data[$key]);

                return $this->writeLockedCacheFile($handle, $data);
            }

            return true;
        } finally {
            if (is_resource($handle)) {
                flock($handle, LOCK_UN);
                fclose($handle);
            }
        }
    }

    public function clear()
    {
        return $this->writeCacheFile([]);
    }

    public function getMultiple($keys, $default = null)
    {
        $keys = $this->iterableToArray($keys);
        $this->validateKeys($keys);

        $data = $this->readCacheFile();
        $results = [];
        $needsWrite = false;

        foreach ($keys as $key) {
            $sanitizedKey = $this->sanitizeKey($key);
            if (!isset($data[$sanitizedKey])) {
                $results[$key] = $default;

                continue;
            }

            if ($this->isExpired($data[$sanitizedKey]['expiry'])) {
                unset($data[$sanitizedKey]);
                $needsWrite = true;
                $results[$key] = $default;

                continue;
            }

            $results[$key] = $data[$sanitizedKey]['value'] ?? $default;
        }

        if ($needsWrite) {
            $this->writeCacheFile($data);
        }

        return $results;
    }

    public function setMultiple($values, $ttl = null)
    {
        $values = $this->iterableToArray($values);
        $this->validateKeys(array_keys($values));

        $data = $this->readCacheFile();
        $expiry = $this->calculateExpiry($ttl);

        foreach ($values as $key => $value) {
            $sanitizedKey = $this->sanitizeKey((string) $key);
            $data[$sanitizedKey] = [
                'value' => $value,
                'expiry' => $expiry,
            ];
        }

        return $this->writeCacheFile($data);
    }

    public function deleteMultiple($keys)
    {
        $keys = $this->iterableToArray($keys);
        $this->validateKeys($keys);

        $data = $this->readCacheFile();
        $deleted = false;

        foreach ($keys as $key) {
            $sanitizedKey = $this->sanitizeKey($key);
            if (isset($data[$sanitizedKey])) {
                unset($data[$sanitizedKey]);
                $deleted = true;
            }
        }

        if ($deleted) {
            return $this->writeCacheFile($data);
        }

        return true;
    }

    public function has($key)
    {
        $data = $this->readCacheFile();
        $key = $this->sanitizeKey($key);

        if (!isset($data[$key])) {
            return false;
        }

        if ($this->isExpired($data[$key]['expiry'])) {
            $this->delete($key);

            return false;
        }

        return true;
    }

    private function readCacheFile()
    {
        if (!file_exists($this->cacheFile) || filesize($this->cacheFile) === 0) {
            return [];
        }

        $handle = @fopen($this->cacheFile, 'rb');
        if ($handle === false) {
            return [];
        }

        try {
            if (!flock($handle, LOCK_SH)) {
                return [];
            }
            $content = stream_get_contents($handle);
            flock($handle, LOCK_UN);

            if ($content === false || $content === '') {
                return [];
            }

            $data = json_decode($content, true);
            if ($data === false) {
                return [];
            }

            return $data;
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }
    }

    private function writeCacheFile($data)
    {
        $jsonData = json_encode($data);

        if (!$jsonData) {
            return false;
        }

        $handle = @fopen($this->cacheFile, 'cb');
        if ($handle === false) {
            return false;
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                return false;
            }
            if (!ftruncate($handle, 0)) {
                return false;
            }
            if (fwrite($handle, $jsonData) === false) {
                return false;
            }
            fflush($handle);
            flock($handle, LOCK_UN);
            @chmod($this->cacheFile, $this->filePermission);

            return true;
        } catch (\Throwable $e) {
            flock($handle, LOCK_UN);

            return false;
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }
    }

    private function ensureDirectoryExists($directory)
    {
        if (!is_dir($directory)) {
            if (!@mkdir($directory, $this->dirPermission, true)) {
                throw new \InvalidArgumentException("Cache directory does not exist and could not be created: {$directory}");
            }
            @chmod($directory, $this->dirPermission);
        }
    }

    private function calculateExpiry($ttl)
    {
        if ($ttl === null) {
            return null;
        }
        $now = time();
        if (is_int($ttl)) {
            return $ttl <= 0 ? $now - 1 : $now + $ttl;
        }
        if ($ttl instanceof \DateInterval) {
            try {
                return (new \DateTimeImmutable())->add($ttl)->getTimestamp();
            } catch (\Throwable $e) {
                return null;
            }
        }
        throw new \InvalidArgumentException('Invalid TTL type provided. Must be null, int, or DateInterval.');
    }

    private function isExpired($expiry)
    {
        return $expiry !== null && time() >= $expiry;
    }

    private function sanitizeKey($key)
    {
        if ($key === '') {
            throw new \InvalidArgumentException('Cache key cannot be empty.');
        }

        return $key;
    }

    private function validateKeys($keys)
    {
        foreach ($keys as $key) {
            if (!is_string($key)) {
                throw new \InvalidArgumentException('Cache key must be a string, got ' . gettype($key));
            }
            $this->sanitizeKey($key);
        }
    }

    private function iterableToArray($iterable)
    {
        if (is_array($iterable)) {
            return $iterable;
        }

        return iterator_to_array($iterable);
    }

    private function readLockedCacheFile($handle)
    {
        if (!is_resource($handle)) {
            return [];
        }

        rewind($handle);
        $content = stream_get_contents($handle);

        if ($content === false || $content === '') {
            return [];
        }

        $data = json_decode($content, true);

        return $data === false ? [] : $data;
    }

    private function writeLockedCacheFile($handle, $data)
    {
        if (!is_resource($handle)) {
            return false;
        }

        $serializedData = json_encode($data);
        if (!$serializedData) {
            return false;
        }

        if (!ftruncate($handle, 0)) {
            return false;
        }

        rewind($handle);

        if (fwrite($handle, $serializedData) === false) {
            return false;
        }

        fflush($handle);
        @chmod($this->cacheFile, $this->filePermission);

        return true;
    }
}
