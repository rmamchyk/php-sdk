<?php

use PHPUnit\Framework\TestCase;

use App\EvolvClient;
use App\HttpClient;
require_once __DIR__ . '/../App/EvolvClient.php';


class ActiveKeysTest extends TestCase {
    protected $client;

    public function setUp(): void {
        $environment = '7f4099bfbc';
        $endpoint = 'https://participants-stg.evolv.ai/';
        $uid = 'user_id';

        $mock = $this->createMock(HttpClient::class);

        // $mock->method('request')
        //     ->willReturn('{"_published":1655911594.5211473,"_client":{"browser":"unspecified","device":"desktop","location":"UA","geo":{"city":"Sarny","country":"UA","region":"56","metro":"","lat":"51.33760","lon":"26.59360","tz":"Europe/Kiev"},"platform":"unspecified"},"_experiments":[{"web":{},"_predicate":{"id":329,"combinator":"and","rules":[{"field":"native.newUser","fieldType":"boolean","operator":"is_true","value":""}]},"home":{"_is_entry_point":true,"_predicate":{"combinator":"and","rules":[{"field":"native.pageCategory","fieldType":"string","operator":"loose_equal","value":"home","type":"attributes","readonly":false,"id":"c21dfad4-f9b3-4e86-8ea4-b743023552ee"}],"readonly":false,"id":"8670b6ba-f56c-4d63-a722-5e3acc975b4f"},"cta_text":{"_is_entry_point":false,"_predicate":null,"_values":true,"_initializers":true},"_initializers":true},"pdp":{"_is_entry_point":false,"_predicate":{"combinator":"and","rules":[{"field":"native.pageCategory","fieldType":"string","operator":"loose_equal","value":"pdp","type":"attributes","readonly":false,"id":"52352c5c-5372-4fcc-b732-f4601e594d03"}],"readonly":false,"id":"f0b82ec4-c68c-4b58-81a5-975c9525c0a5"},"page_layout":{"_is_entry_point":false,"_predicate":{"combinator":"and","rules":[{"field":"extra_key","operator":"not_exists","value":"","type":"custom_attribute","readonly":false,"id":"f5ab3069-c022-4a5c-a736-30028547155f"}],"readonly":false,"id":"6ce9f491-3f36-43de-b155-b6389728be45"},"_values":true,"_initializers":true},"_initializers":true},"id":"a338ff9f0f","_paused":false}]}');

        $this->client = new EvolvClient($environment, $endpoint);
        $this->client->initialize($uid);
    }

    /**
     * @test
    */
    public function shouldReturnEmptyWhenRootPredicateNotMatched() {
        // Act
        $activeKeys = $this->client->getActiveKeys();

        // Assert
        $this->assertEmpty($activeKeys);
    }

    /**
     * @test
    */
    public function shouldReturnEmptyWhenRootMatchedButChildPredicatesDoNot() {
        // Arrange
        $this->client->context->set('native.newUser', true, true);

        // Act
        $activeKeys = $this->client->getActiveKeys();

        // Assert
        $this->assertEmpty($activeKeys);
    }

    /**
     * @test
    */
    public function shouldReturnHomeKeysWhenPredicateMached() {
        // Arrange
        $this->client->context->set('native.newUser', true, true);
        $this->client->context->set('native.pageCategory', 'home', true);

        // Act
        $activeKeys = $this->client->getActiveKeys();

        // Assert
        $this->assertCount(2, $activeKeys);
        $this->assertEquals(['home', 'home.cta_text'], $activeKeys);
    }

    /**
     * @test
    */
    public function shouldReturnPdpKeysWhenPredicateMached() {
        // Arrange
        $this->client->context->set('native.newUser', true, true);
        $this->client->context->set('native.pageCategory', 'pdp', true);

        // Act
        $activeKeys = $this->client->getActiveKeys();

        // Assert
        $this->assertCount(2, $activeKeys);
        $this->assertEquals(['pdp', 'pdp.page_layout'], $activeKeys);
    }

    /**
     * @test
    */
    public function shouldNotReturnPageLayoutWhenPredicateNotMached() {
        // Arrange
        $this->client->context->set('native.newUser', true, true);
        $this->client->context->set('native.pageCategory', 'pdp', true);
        $this->client->context->set('extra_key', true, true);

        // Act
        $activeKeys = $this->client->getActiveKeys();

        // Assert
        $this->assertCount(1, $activeKeys);
        $this->assertEquals(['pdp'], $activeKeys);
    }
}
