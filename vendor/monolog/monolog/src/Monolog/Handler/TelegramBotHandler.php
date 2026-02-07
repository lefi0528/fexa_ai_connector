<?php declare(strict_types=1);



namespace Monolog\Handler;

use RuntimeException;
use Monolog\Level;
use Monolog\Utils;
use Monolog\LogRecord;


class TelegramBotHandler extends AbstractProcessingHandler
{
    private const BOT_API = 'https://api.telegram.org/bot';

    
    private const AVAILABLE_PARSE_MODES = [
        'HTML',
        'MarkdownV2',
        'Markdown', 
    ];

    
    private const MAX_MESSAGE_LENGTH = 4096;

    
    private string $apiKey;

    
    private string $channel;

    
    private string|null $parseMode;

    
    private bool|null $disableWebPagePreview;

    
    private bool|null $disableNotification;

    
    private bool $splitLongMessages;

    
    private bool $delayBetweenMessages;

    
    private int|null $topic;

    
    public function __construct(
        string $apiKey,
        string $channel,
        $level = Level::Debug,
        bool   $bubble = true,
        ?string $parseMode = null,
        ?bool   $disableWebPagePreview = null,
        ?bool   $disableNotification = null,
        bool   $splitLongMessages = false,
        bool   $delayBetweenMessages = false,
        ?int   $topic = null
    ) {
        if (!\extension_loaded('curl')) {
            throw new MissingExtensionException('The curl extension is needed to use the TelegramBotHandler');
        }

        parent::__construct($level, $bubble);

        $this->apiKey = $apiKey;
        $this->channel = $channel;
        $this->setParseMode($parseMode);
        $this->disableWebPagePreview($disableWebPagePreview);
        $this->disableNotification($disableNotification);
        $this->splitLongMessages($splitLongMessages);
        $this->delayBetweenMessages($delayBetweenMessages);
        $this->setTopic($topic);
    }

    
    public function setParseMode(string|null $parseMode = null): self
    {
        if ($parseMode !== null && !\in_array($parseMode, self::AVAILABLE_PARSE_MODES, true)) {
            throw new \InvalidArgumentException('Unknown parseMode, use one of these: ' . implode(', ', self::AVAILABLE_PARSE_MODES) . '.');
        }

        $this->parseMode = $parseMode;

        return $this;
    }

    
    public function disableWebPagePreview(bool|null $disableWebPagePreview = null): self
    {
        $this->disableWebPagePreview = $disableWebPagePreview;

        return $this;
    }

    
    public function disableNotification(bool|null $disableNotification = null): self
    {
        $this->disableNotification = $disableNotification;

        return $this;
    }

    
    public function splitLongMessages(bool $splitLongMessages = false): self
    {
        $this->splitLongMessages = $splitLongMessages;

        return $this;
    }

    
    public function delayBetweenMessages(bool $delayBetweenMessages = false): self
    {
        $this->delayBetweenMessages = $delayBetweenMessages;

        return $this;
    }

    
    public function setTopic(?int $topic = null): self
    {
        $this->topic = $topic;

        return $this;
    }

    
    public function handleBatch(array $records): void
    {
        $messages = [];

        foreach ($records as $record) {
            if (!$this->isHandling($record)) {
                continue;
            }

            if (\count($this->processors) > 0) {
                $record = $this->processRecord($record);
            }

            $messages[] = $record;
        }

        if (\count($messages) > 0) {
            $this->send((string) $this->getFormatter()->formatBatch($messages));
        }
    }

    
    protected function write(LogRecord $record): void
    {
        $this->send($record->formatted);
    }

    
    protected function send(string $message): void
    {
        $messages = $this->handleMessageLength($message);

        foreach ($messages as $key => $msg) {
            if ($this->delayBetweenMessages && $key > 0) {
                sleep(1);
            }

            $this->sendCurl($msg);
        }
    }

    protected function sendCurl(string $message): void
    {
        $ch = curl_init();
        $url = self::BOT_API . $this->apiKey . '/SendMessage';
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $params = [
            'text' => $message,
            'chat_id' => $this->channel,
            'parse_mode' => $this->parseMode,
            'disable_web_page_preview' => $this->disableWebPagePreview,
            'disable_notification' => $this->disableNotification,
        ];
        if ($this->topic !== null) {
            $params['message_thread_id'] = $this->topic;
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

        $result = Curl\Util::execute($ch);
        if (!\is_string($result)) {
            throw new RuntimeException('Telegram API error. Description: No response');
        }
        $result = json_decode($result, true);

        if ($result['ok'] === false) {
            throw new RuntimeException('Telegram API error. Description: ' . $result['description']);
        }
    }

    
    private function handleMessageLength(string $message): array
    {
        $truncatedMarker = ' (...truncated)';
        if (!$this->splitLongMessages && \strlen($message) > self::MAX_MESSAGE_LENGTH) {
            return [Utils::substr($message, 0, self::MAX_MESSAGE_LENGTH - \strlen($truncatedMarker)) . $truncatedMarker];
        }

        return str_split($message, self::MAX_MESSAGE_LENGTH);
    }
}
