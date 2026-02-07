<?php

namespace React\Http\Io;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use InvalidArgumentException;
use RuntimeException;


final class UploadedFile implements UploadedFileInterface
{
    
    private $stream;

    
    private $size;

    
    private $error;

    
    private $filename;

    
    private $mediaType;

    
    public function __construct(StreamInterface $stream, $size, $error, $filename, $mediaType)
    {
        $this->stream = $stream;
        $this->size = $size;

        if (!\is_int($error) || !\in_array($error, array(
            \UPLOAD_ERR_OK,
            \UPLOAD_ERR_INI_SIZE,
            \UPLOAD_ERR_FORM_SIZE,
            \UPLOAD_ERR_PARTIAL,
            \UPLOAD_ERR_NO_FILE,
            \UPLOAD_ERR_NO_TMP_DIR,
            \UPLOAD_ERR_CANT_WRITE,
            \UPLOAD_ERR_EXTENSION,
        ))) {
            throw new InvalidArgumentException(
                'Invalid error code, must be an UPLOAD_ERR_* constant'
            );
        }
        $this->error = $error;
        $this->filename = $filename;
        $this->mediaType = $mediaType;
    }

    
    public function getStream()
    {
        if ($this->error !== \UPLOAD_ERR_OK) {
            throw new RuntimeException('Cannot retrieve stream due to upload error');
        }

        return $this->stream;
    }

    
    public function moveTo($targetPath)
    {
       throw new RuntimeException('Not implemented');
    }

    
    public function getSize()
    {
        return $this->size;
    }

    
    public function getError()
    {
        return $this->error;
    }

    
    public function getClientFilename()
    {
        return $this->filename;
    }

    
    public function getClientMediaType()
    {
        return $this->mediaType;
    }
}
