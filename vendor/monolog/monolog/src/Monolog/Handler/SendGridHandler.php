<?php declare(strict_types=1);



namespace Monolog\Handler;

use Monolog\Level;
use Monolog\Utils;


class SendGridHandler extends MailHandler
{
    
    protected string $apiUser;
    
    protected array $to;

    
    public function __construct(
        string|null $apiUser,
        protected string $apiKey,
        protected string $from,
        array|string $to,
        protected string $subject,
        int|string|Level $level = Level::Error,
        bool $bubble = true,
        
        private readonly string $apiHost = 'api.sendgrid.com',
    ) {
        if (!\extension_loaded('curl')) {
            throw new MissingExtensionException('The curl extension is needed to use the SendGridHandler');
        }

        $this->to = (array) $to;
        
        $this->apiUser = $apiUser ?? '';
        parent::__construct($level, $bubble);
    }

    protected function send(string $content, array $records): void
    {
        $body = [];
        $body['personalizations'] = [];
        $body['from']['email'] = $this->from;
        foreach ($this->to as $recipient) {
            $body['personalizations'][]['to'][]['email'] = $recipient;
        }
        $body['subject'] = $this->subject;

        if ($this->isHtmlBody($content)) {
            $body['content'][] = [
                'type' => 'text/html',
                'value' => $content,
            ];
        } else {
            $body['content'][] = [
                'type' => 'text/plain',
                'value' => $content,
            ];
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer '.$this->apiKey,
        ]);
        curl_setopt($ch, CURLOPT_URL, 'https://'.$this->apiHost.'/v3/mail/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, Utils::jsonEncode($body));

        Curl\Util::execute($ch, 2);
    }
}
