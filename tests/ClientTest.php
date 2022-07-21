<?php

use PHPUnit\Framework\TestCase;

use App\EvolvClient;
require_once __DIR__ . '/../App/EvolvClient.php';


class ClientTest extends TestCase {

    public function testInitializeMakesTwoRequests() {
        $environment = '7f4099bfbc';
        $endpoint = 'https://participants-stg.evolv.ai/';
        $uid = 'user_id';

        $client = new EvolvClient($environment, $endpoint);
        $client->initialize($uid);

        // TODO: verify two requests are made
    }

}
