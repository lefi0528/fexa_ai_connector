<?php declare(strict_types=1);



namespace Monolog\Handler;

use Closure;
use Monolog\Level;
use Monolog\LogRecord;
use Monolog\Utils;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\LineFormatter;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;


class SymfonyMailerHandler extends MailHandler
{
    protected MailerInterface|TransportInterface $mailer;
    
    private Email|Closure $emailTemplate;

    
    public function __construct($mailer, Email|Closure $email, int|string|Level $level = Level::Error, bool $bubble = true)
    {
        parent::__construct($level, $bubble);

        $this->mailer = $mailer;
        $this->emailTemplate = $email;
    }

    
    protected function send(string $content, array $records): void
    {
        $this->mailer->send($this->buildMessage($content, $records));
    }

    
    protected function getSubjectFormatter(?string $format): FormatterInterface
    {
        return new LineFormatter($format);
    }

    
    protected function buildMessage(string $content, array $records): Email
    {
        $message = null;
        if ($this->emailTemplate instanceof Email) {
            $message = clone $this->emailTemplate;
        } elseif (\is_callable($this->emailTemplate)) {
            $message = ($this->emailTemplate)($content, $records);
        }

        if (!$message instanceof Email) {
            $record = reset($records);

            throw new \InvalidArgumentException('Could not resolve message as instance of Email or a callable returning it' . ($record instanceof LogRecord ? Utils::getRecordMessageForException($record) : ''));
        }

        if (\count($records) > 0) {
            $subjectFormatter = $this->getSubjectFormatter($message->getSubject());
            $message->subject($subjectFormatter->format($this->getHighestRecord($records)));
        }

        if ($this->isHtmlBody($content)) {
            if (null !== ($charset = $message->getHtmlCharset())) {
                $message->html($content, $charset);
            } else {
                $message->html($content);
            }
        } else {
            if (null !== ($charset = $message->getTextCharset())) {
                $message->text($content, $charset);
            } else {
                $message->text($content);
            }
        }

        return $message->date(new \DateTimeImmutable());
    }
}
