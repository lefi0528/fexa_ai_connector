<?php declare(strict_types=1);

namespace PHPUnit\Util\Http;


interface Downloader
{
    
    public function download(string $url): false|string;
}
