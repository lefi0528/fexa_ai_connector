<?php declare(strict_types=1);



namespace Monolog\Handler\Slack;

use Monolog\Level;
use Monolog\Utils;
use Monolog\Formatter\NormalizerFormatter;
use Monolog\Formatter\FormatterInterface;
use Monolog\LogRecord;


class SlackRecord
{
    public const COLOR_DANGER = 'danger';

    public const COLOR_WARNING = 'warning';

    public const COLOR_GOOD = 'good';

    public const COLOR_DEFAULT = '#e3e4e6';

    
    private string|null $channel;

    
    private string|null $username;

    
    private string|null $userIcon;

    
    private bool $useAttachment;

    
    private bool $useShortAttachment;

    
    private bool $includeContextAndExtra;

    
    private array $excludeFields;

    private FormatterInterface|null $formatter;

    private NormalizerFormatter $normalizerFormatter;

    
    public function __construct(
        ?string $channel = null,
        ?string $username = null,
        bool $useAttachment = true,
        ?string $userIcon = null,
        bool $useShortAttachment = false,
        bool $includeContextAndExtra = false,
        array $excludeFields = [],
        FormatterInterface|null $formatter = null
    ) {
        $this
            ->setChannel($channel)
            ->setUsername($username)
            ->useAttachment($useAttachment)
            ->setUserIcon($userIcon)
            ->useShortAttachment($useShortAttachment)
            ->includeContextAndExtra($includeContextAndExtra)
            ->excludeFields($excludeFields)
            ->setFormatter($formatter);

        if ($this->includeContextAndExtra) {
            $this->normalizerFormatter = new NormalizerFormatter();
        }
    }

    
    public function getSlackData(LogRecord $record): array
    {
        $dataArray = [];

        if ($this->username !== null) {
            $dataArray['username'] = $this->username;
        }

        if ($this->channel !== null) {
            $dataArray['channel'] = $this->channel;
        }

        if ($this->formatter !== null && !$this->useAttachment) {
            $message = $this->formatter->format($record);
        } else {
            $message = $record->message;
        }

        $recordData = $this->removeExcludedFields($record);

        if ($this->useAttachment) {
            $attachment = [
                'fallback'  => $message,
                'text'      => $message,
                'color'     => $this->getAttachmentColor($record->level),
                'fields'    => [],
                'mrkdwn_in' => ['fields'],
                'ts'        => $recordData['datetime']->getTimestamp(),
                'footer'      => $this->username,
                'footer_icon' => $this->userIcon,
            ];

            if ($this->useShortAttachment) {
                $attachment['title'] = $recordData['level_name'];
            } else {
                $attachment['title'] = 'Message';
                $attachment['fields'][] = $this->generateAttachmentField('Level', $recordData['level_name']);
            }

            if ($this->includeContextAndExtra) {
                foreach (['extra', 'context'] as $key) {
                    if (!isset($recordData[$key]) || \count($recordData[$key]) === 0) {
                        continue;
                    }

                    if ($this->useShortAttachment) {
                        $attachment['fields'][] = $this->generateAttachmentField(
                            $key,
                            $recordData[$key]
                        );
                    } else {
                        
                        $attachment['fields'] = array_merge(
                            $attachment['fields'],
                            $this->generateAttachmentFields($recordData[$key])
                        );
                    }
                }
            }

            $dataArray['attachments'] = [$attachment];
        } else {
            $dataArray['text'] = $message;
        }

        if ($this->userIcon !== null) {
            if (false !== ($iconUrl = filter_var($this->userIcon, FILTER_VALIDATE_URL))) {
                $dataArray['icon_url'] = $iconUrl;
            } else {
                $dataArray['icon_emoji'] = ":{$this->userIcon}:";
            }
        }

        return $dataArray;
    }

    
    public function getAttachmentColor(Level $level): string
    {
        return match ($level) {
            Level::Error, Level::Critical, Level::Alert, Level::Emergency => static::COLOR_DANGER,
            Level::Warning => static::COLOR_WARNING,
            Level::Info, Level::Notice => static::COLOR_GOOD,
            Level::Debug => static::COLOR_DEFAULT
        };
    }

    
    public function stringify(array $fields): string
    {
        
        $normalized = $this->normalizerFormatter->normalizeValue($fields);

        $hasSecondDimension = \count(array_filter($normalized, 'is_array')) > 0;
        $hasOnlyNonNumericKeys = \count(array_filter(array_keys($normalized), 'is_numeric')) === 0;

        return $hasSecondDimension || $hasOnlyNonNumericKeys
            ? Utils::jsonEncode($normalized, JSON_PRETTY_PRINT|Utils::DEFAULT_JSON_FLAGS)
            : Utils::jsonEncode($normalized, Utils::DEFAULT_JSON_FLAGS);
    }

    
    public function setChannel(?string $channel = null): self
    {
        $this->channel = $channel;

        return $this;
    }

    
    public function setUsername(?string $username = null): self
    {
        $this->username = $username;

        return $this;
    }

    
    public function useAttachment(bool $useAttachment = true): self
    {
        $this->useAttachment = $useAttachment;

        return $this;
    }

    
    public function setUserIcon(?string $userIcon = null): self
    {
        $this->userIcon = $userIcon;

        if (\is_string($userIcon)) {
            $this->userIcon = trim($userIcon, ':');
        }

        return $this;
    }

    
    public function useShortAttachment(bool $useShortAttachment = false): self
    {
        $this->useShortAttachment = $useShortAttachment;

        return $this;
    }

    
    public function includeContextAndExtra(bool $includeContextAndExtra = false): self
    {
        $this->includeContextAndExtra = $includeContextAndExtra;

        if ($this->includeContextAndExtra) {
            $this->normalizerFormatter = new NormalizerFormatter();
        }

        return $this;
    }

    
    public function excludeFields(array $excludeFields = []): self
    {
        $this->excludeFields = $excludeFields;

        return $this;
    }

    
    public function setFormatter(?FormatterInterface $formatter = null): self
    {
        $this->formatter = $formatter;

        return $this;
    }

    
    private function generateAttachmentField(string $title, $value): array
    {
        $value = \is_array($value)
            ? sprintf('```%s```', substr($this->stringify($value), 0, 1990))
            : $value;

        return [
            'title' => ucfirst($title),
            'value' => $value,
            'short' => false,
        ];
    }

    
    private function generateAttachmentFields(array $data): array
    {
        
        $normalized = $this->normalizerFormatter->normalizeValue($data);

        $fields = [];
        foreach ($normalized as $key => $value) {
            $fields[] = $this->generateAttachmentField((string) $key, $value);
        }

        return $fields;
    }

    
    private function removeExcludedFields(LogRecord $record): array
    {
        $recordData = $record->toArray();
        foreach ($this->excludeFields as $field) {
            $keys = explode('.', $field);
            $node = &$recordData;
            $lastKey = end($keys);
            foreach ($keys as $key) {
                if (!isset($node[$key])) {
                    break;
                }
                if ($lastKey === $key) {
                    unset($node[$key]);
                    break;
                }
                $node = &$node[$key];
            }
        }

        return $recordData;
    }
}
