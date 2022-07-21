<?php

declare(strict_types=1);

namespace App;

use App\EvolvContext;

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/EvolvStore.php';
require_once __DIR__ . '/Beacon.php';

const ENDPOINT_PATTERN = "/\/(v\d+)\/\w+\/([a-z]+)$/i";

class Beacon {
    private string $endpoint;
    private EvolvContext $context;
    private array $messages = [];
    private bool $v1Events;

    public function __construct(string $endpoint, EvolvContext $context)
    {
        $this->endpoint = $endpoint;
        $this->context = $context;

        preg_match(ENDPOINT_PATTERN, $endpoint, $matches);
        $this->v1Events = $matches && $matches[1] === 'v1' && $matches[2] === 'events';
    }

    private function send($payload) {

        $data = json_encode($payload);

        echo $this->endpoint;

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $this->endpoint);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $headers = array(
            "Accept: application/json",
            "Content-Type: application/json",
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $data = <<<DATA
        $data
        DATA;

        echo '<pre>';
        print_r($data);
        echo '</pre>';

        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        $res = curl_exec($curl);

        echo "<pre>";
        print_r($res);
        echo "</pre>";

        curl_close($curl);

        $this->messages = [];
    }

    private function wrapMessages()
    {
        return [
            'uid' => $this->context->uid,
            'client' => 'php-sdk',
            'messages' => $this->messages
        ];
    }

    private function transmit()
    {
        if (!count($this->messages)) {
            return;
        }

        if ($this->v1Events) {
            foreach($this->messages as $message) {
                $editedMessage = $message;
                $editedMessage = $message['payload'] ?? [];
                $editedMessage['type'] = $message['type'];

                $this->send($editedMessage);
            }
        } else {
            $this->send($this->wrapMessages());
        }
    }

    public function emit(string $type, $payload, bool $flush = false)
    {
        $this->messages[] = [
            'type' => $type,
            'payload' => $payload,
            'timestamp' => time()
        ];

        $this->transmit();
    }

    public function flush() {
        $this->transmit();
    }
} 