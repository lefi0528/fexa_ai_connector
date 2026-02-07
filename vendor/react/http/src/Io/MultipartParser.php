<?php

namespace React\Http\Io;

use Psr\Http\Message\ServerRequestInterface;


final class MultipartParser
{
    
    private $request;

    
    private $maxFileSize;

    
    private $maxMultipartBodyParts;

    
    private $maxInputVars = 1000;

    
    private $maxInputNestingLevel = 64;

    
    private $uploadMaxFilesize;

    
    private $maxFileUploads;

    private $multipartBodyPartCount = 0;
    private $postCount = 0;
    private $filesCount = 0;
    private $emptyCount = 0;
    private $cursor = 0;

    
    public function __construct($uploadMaxFilesize = null, $maxFileUploads = null)
    {
        $var = \ini_get('max_input_vars');
        if ($var !== false) {
            $this->maxInputVars = (int)$var;
        }
        $var = \ini_get('max_input_nesting_level');
        if ($var !== false) {
            $this->maxInputNestingLevel = (int)$var;
        }

        if ($uploadMaxFilesize === null) {
            $uploadMaxFilesize = \ini_get('upload_max_filesize');
        }

        $this->uploadMaxFilesize = IniUtil::iniSizeToBytes($uploadMaxFilesize);
        $this->maxFileUploads = $maxFileUploads === null ? (\ini_get('file_uploads') === '' ? 0 : (int)\ini_get('max_file_uploads')) : (int)$maxFileUploads;

        $this->maxMultipartBodyParts = $this->maxInputVars + $this->maxFileUploads;
    }

    public function parse(ServerRequestInterface $request)
    {
        $contentType = $request->getHeaderLine('content-type');
        if(!\preg_match('/boundary="?(.*?)"?$/', $contentType, $matches)) {
            return $request;
        }

        $this->request = $request;
        $this->parseBody('--' . $matches[1], (string)$request->getBody());

        $request = $this->request;
        $this->request = null;
        $this->multipartBodyPartCount = 0;
        $this->cursor = 0;
        $this->postCount = 0;
        $this->filesCount = 0;
        $this->emptyCount = 0;
        $this->maxFileSize = null;

        return $request;
    }

    private function parseBody($boundary, $buffer)
    {
        $len = \strlen($boundary);

        
        $this->cursor = \strpos($buffer, $boundary . "\r\n");

        while ($this->cursor !== false) {
            
            
            $this->cursor += $len + 2;
            $end = \strpos($buffer, "\r\n" . $boundary, $this->cursor);
            if ($end === false) {
                break;
            }

            
            $this->parsePart(\substr($buffer, $this->cursor, $end - $this->cursor));
            $this->cursor = $end;

            if (++$this->multipartBodyPartCount > $this->maxMultipartBodyParts) {
                break;
            }
        }
    }

    private function parsePart($chunk)
    {
        $pos = \strpos($chunk, "\r\n\r\n");
        if ($pos === false) {
            return;
        }

        $headers = $this->parseHeaders((string)substr($chunk, 0, $pos));
        $body = (string)\substr($chunk, $pos + 4);

        if (!isset($headers['content-disposition'])) {
            return;
        }

        $name = $this->getParameterFromHeader($headers['content-disposition'], 'name');
        if ($name === null) {
            return;
        }

        $filename = $this->getParameterFromHeader($headers['content-disposition'], 'filename');
        if ($filename !== null) {
            $this->parseFile(
                $name,
                $filename,
                isset($headers['content-type'][0]) ? $headers['content-type'][0] : null,
                $body
            );
        } else {
            $this->parsePost($name, $body);
        }
    }

    private function parseFile($name, $filename, $contentType, $contents)
    {
        $file = $this->parseUploadedFile($filename, $contentType, $contents);
        if ($file === null) {
            return;
        }

        $this->request = $this->request->withUploadedFiles($this->extractPost(
            $this->request->getUploadedFiles(),
            $name,
            $file
        ));
    }

    private function parseUploadedFile($filename, $contentType, $contents)
    {
        $size = \strlen($contents);

        
        if ($size === 0 && $filename === '') {
            
            if (++$this->emptyCount + $this->filesCount > $this->maxInputVars) {
                return;
            }

            return new UploadedFile(
                new BufferedBody(''),
                $size,
                \UPLOAD_ERR_NO_FILE,
                $filename,
                $contentType
            );
        }

        
        if (++$this->filesCount > $this->maxFileUploads) {
            return;
        }

        
        if ($size > $this->uploadMaxFilesize) {
            return new UploadedFile(
                new BufferedBody(''),
                $size,
                \UPLOAD_ERR_INI_SIZE,
                $filename,
                $contentType
            );
        }

        
        if ($this->maxFileSize !== null && $size > $this->maxFileSize) {
            return new UploadedFile(
                new BufferedBody(''),
                $size,
                \UPLOAD_ERR_FORM_SIZE,
                $filename,
                $contentType
            );
        }

        return new UploadedFile(
            new BufferedBody($contents),
            $size,
            \UPLOAD_ERR_OK,
            $filename,
            $contentType
        );
    }

    private function parsePost($name, $value)
    {
        
        if (++$this->postCount > $this->maxInputVars) {
            return;
        }

        $this->request = $this->request->withParsedBody($this->extractPost(
            $this->request->getParsedBody(),
            $name,
            $value
        ));

        if (\strtoupper($name) === 'MAX_FILE_SIZE') {
            $this->maxFileSize = (int)$value;

            if ($this->maxFileSize === 0) {
                $this->maxFileSize = null;
            }
        }
    }

    private function parseHeaders($header)
    {
        $headers = array();

        foreach (\explode("\r\n", \trim($header)) as $line) {
            $parts = \explode(':', $line, 2);
            if (!isset($parts[1])) {
                continue;
            }

            $key = \strtolower(trim($parts[0]));
            $values = \explode(';', $parts[1]);
            $values = \array_map('trim', $values);
            $headers[$key] = $values;
        }

        return $headers;
    }

    private function getParameterFromHeader(array $header, $parameter)
    {
        foreach ($header as $part) {
            if (\preg_match('/' . $parameter . '="?(.*?)"?$/', $part, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    private function extractPost($postFields, $key, $value)
    {
        $chunks = \explode('[', $key);
        if (\count($chunks) == 1) {
            $postFields[$key] = $value;
            return $postFields;
        }

        
        if (isset($chunks[$this->maxInputNestingLevel])) {
            return $postFields;
        }

        $chunkKey = \rtrim($chunks[0], ']');
        $parent = &$postFields;
        for ($i = 1; isset($chunks[$i]); $i++) {
            $previousChunkKey = $chunkKey;

            if ($previousChunkKey === '') {
                $parent[] = array();
                \end($parent);
                $parent = &$parent[\key($parent)];
            } else {
                if (!isset($parent[$previousChunkKey]) || !\is_array($parent[$previousChunkKey])) {
                    $parent[$previousChunkKey] = array();
                }
                $parent = &$parent[$previousChunkKey];
            }

            $chunkKey = \rtrim($chunks[$i], ']');
        }

        if ($chunkKey === '') {
            $parent[] = $value;
        } else {
            $parent[$chunkKey] = $value;
        }

        return $postFields;
    }
}
