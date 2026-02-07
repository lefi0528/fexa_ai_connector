<?php declare(strict_types=1);



namespace Monolog\Handler;

use Monolog\Level;
use Monolog\Formatter\LineFormatter;


class NativeMailerHandler extends MailHandler
{
    
    protected array $to;

    
    protected string $subject;

    
    protected array $headers = [];

    
    protected array $parameters = [];

    
    protected int $maxColumnWidth;

    
    protected string|null $contentType = null;

    
    protected string $encoding = 'utf-8';

    
    public function __construct(string|array $to, string $subject, string $from, int|string|Level $level = Level::Error, bool $bubble = true, int $maxColumnWidth = 70)
    {
        parent::__construct($level, $bubble);
        $this->to = (array) $to;
        $this->subject = $subject;
        $this->addHeader(sprintf('From: %s', $from));
        $this->maxColumnWidth = $maxColumnWidth;
    }

    
    public function addHeader($headers): self
    {
        foreach ((array) $headers as $header) {
            if (strpos($header, "\n") !== false || strpos($header, "\r") !== false) {
                throw new \InvalidArgumentException('Headers can not contain newline characters for security reasons');
            }
            $this->headers[] = $header;
        }

        return $this;
    }

    
    public function addParameter($parameters): self
    {
        $this->parameters = array_merge($this->parameters, (array) $parameters);

        return $this;
    }

    
    protected function send(string $content, array $records): void
    {
        $contentType = $this->getContentType() ?? ($this->isHtmlBody($content) ? 'text/html' : 'text/plain');

        if ($contentType !== 'text/html') {
            $content = wordwrap($content, $this->maxColumnWidth);
        }

        $headers = ltrim(implode("\r\n", $this->headers) . "\r\n", "\r\n");
        $headers .= 'Content-type: ' . $contentType . '; charset=' . $this->getEncoding() . "\r\n";
        if ($contentType === 'text/html' && false === strpos($headers, 'MIME-Version:')) {
            $headers .= 'MIME-Version: 1.0' . "\r\n";
        }

        $subjectFormatter = new LineFormatter($this->subject);
        $subject = $subjectFormatter->format($this->getHighestRecord($records));

        $parameters = implode(' ', $this->parameters);
        foreach ($this->to as $to) {
            $this->mail($to, $subject, $content, $headers, $parameters);
        }
    }

    public function getContentType(): ?string
    {
        return $this->contentType;
    }

    public function getEncoding(): string
    {
        return $this->encoding;
    }

    
    public function setContentType(string $contentType): self
    {
        if (strpos($contentType, "\n") !== false || strpos($contentType, "\r") !== false) {
            throw new \InvalidArgumentException('The content type can not contain newline characters to prevent email header injection');
        }

        $this->contentType = $contentType;

        return $this;
    }

    
    public function setEncoding(string $encoding): self
    {
        if (strpos($encoding, "\n") !== false || strpos($encoding, "\r") !== false) {
            throw new \InvalidArgumentException('The encoding can not contain newline characters to prevent email header injection');
        }

        $this->encoding = $encoding;

        return $this;
    }


    protected function mail(string $to, string $subject, string $content, string $headers, string $parameters): void
    {
        mail($to, $subject, $content, $headers, $parameters);
    }
}
