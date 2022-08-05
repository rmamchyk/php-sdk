<?php

use PHPUnit\Framework\TestCase;

use Evolv\EvolvClient;
use Evolv\HttpClient;

class ActiveKeysTest extends TestCase
{
    protected $client;
    protected $environment = "1";
    protected $endpoint = "1";
    protected $uid = "1";

    public function setUp(): void
    {
        $arr_location = '[{"uid":"user_id","eid":"32fe9d5af1","cid":"3f2030cbd013:32fe9d5af1","genome":{"checkout":{"payment_method":"Credit Card"},"cart":{"items_limit":10}},"audience_query":{},"ordinal":1,"group_id":"7bfd433f-cd4f-4632-a1ad-83fb19525ab5","excluded":false},{"uid":"user_id","eid":"5dd1f6cdb5","cid":"ef019bc45aa1:5dd1f6cdb5","genome":{"home":{"cta_text":"This way to the PDP!"},"pdp":{"page_layout":"Layout 1"}},"audience_query":{"id":1256,"name":"New Users","combinator":"and","rules":[{"field":"native.newUser","operator":"is_true","value":""}]},"ordinal":1,"group_id":"47a71ff2-f2d8-4ccb-92f9-8ec21a3974ad","excluded":false}]';
        $arr_config = '{"_published":1658414275.2701473,"_client":{"browser":"unspecified","device":"desktop","location":"UA","geo":{"city":"Kyiv","country":"UA","region":"30","metro":"","lat":"50.45800","lon":"30.53030","tz":"Europe/Kiev"},"platform":"unspecified"},"_experiments":[{"web":{},"_predicate":{},"checkout":{"_is_entry_point":true,"_predicate":{"combinator":"and","rules":[{"field":"loggedIn","operator":"is_true","value":"","type":"custom_attribute","readonly":false,"id":"9abdbb75-61db-4787-9b00-798f30e21f6c"}],"readonly":false,"id":"992ad0c2-54ea-419e-9805-55335152a18d"},"payment_method":{"_is_entry_point":false,"_predicate":null,"_values":true,"_initializers":true},"_initializers":true},"cart":{"_is_entry_point":false,"_predicate":null,"items_limit":{"_is_entry_point":false,"_predicate":null,"_values":true,"_initializers":true},"_initializers":true},"id":"32fe9d5af1","_paused":false}, {"web":{},"_predicate":{"id":1256,"combinator":"and","rules":[{"field":"native.newUser","operator":"is_true","value":""}]},"home":{"_is_entry_point":true,"_predicate":{"combinator":"and","rules":[{"field":"native.pageCategory","fieldType":"string","operator":"loose_equal","value":"home","type":"attributes","readonly":false,"id":"c21dfad4-f9b3-4e86-8ea4-b743023552ee"}],"readonly":false,"id":"8670b6ba-f56c-4d63-a722-5e3acc975b4f"},"cta_text":{"_is_entry_point":false,"_predicate":null,"_values":true,"_initializers":true},"_initializers":true},"pdp":{"_is_entry_point":false,"_predicate":{"combinator":"and","rules":[{"field":"native.pageCategory","fieldType":"string","operator":"loose_equal","value":"pdp","type":"attributes","readonly":false,"id":"52352c5c-5372-4fcc-b732-f4601e594d03"}],"readonly":false,"id":"f0b82ec4-c68c-4b58-81a5-975c9525c0a5"},"page_layout":{"_is_entry_point":false,"_predicate":{"combinator":"and","rules":[{"field":"extra_key","operator":"not_exists","value":"","type":"custom_attribute","readonly":false,"id":"f5ab3069-c022-4a5c-a736-30028547155f"}],"readonly":false,"id":"6ce9f491-3f36-43de-b155-b6389728be45"},"_values":true,"_initializers":true},"_initializers":true},"id":"5dd1f6cdb5","_paused":false}]}';
        $mock = $this->createMock(HttpClient::class);
        $mock->method('request')
            ->willReturn($arr_location, $arr_config);
        $this->client = new EvolvClient($this->environment, $this->endpoint);
        $this->client->initialize($this->uid, $mock);
    }

    /**
     * @test
     */
    public function shouldReturnEmptyWhenRootPredicateNotMatched()
    {
        // Act
        $activeKeys = $this->client->getActiveKeys();

        // Assert
        $this->assertCount(2, $activeKeys);
    }

    /**
     * @test
     */
    public function shouldReturnEmptyWhenRootMatchedButChildPredicatesDoNot()
    {
        // Arrange
        $this->client->context->set('native.newUser', true, true);

        // Act
        $activeKeys = $this->client->getActiveKeys();

        // Assert
        $this->assertCount(2, $activeKeys);
    }

    /**
     * @test
     */
    public function shouldReturnHomeKeysWhenPredicateMached()
    {
        // Arrange
        $this->client->context->set('native.newUser', true, true);
        $this->client->context->set('native.pageCategory', 'home', true);

        // Act
        $activeKeys = $this->client->getActiveKeys();
        // Assert
        $this->assertCount(4, $activeKeys);
        $this->assertEquals(['cart.items_limit', 'cart', 'home.cta_text', 'home'], $activeKeys);
    }

    /**
     * @test
     */
    public function shouldReturnPdpKeysWhenPredicateMached()
    {
        // Arrange
        $this->client->context->set('native.newUser', true, true);
        $this->client->context->set('native.pageCategory', 'pdp', true);

        // Act
        $activeKeys = $this->client->getActiveKeys();

        // Assert
        $this->assertCount(4, $activeKeys);
        $this->assertEquals(['cart.items_limit', 'cart', 'pdp.page_layout', 'pdp'], $activeKeys);
    }

    /**
     * @test
     */
    public function shouldNotReturnPageLayoutWhenPredicateNotMached()
    {
        // Arrange
        $this->client->context->set('native.newUser', true, true);
        $this->client->context->set('native.pageCategory', 'pdp', true);
        $this->client->context->set('extra_key', true, true);

        // Act
        $activeKeys = $this->client->getActiveKeys();
        // Assert
        $this->assertCount(3, $activeKeys);
        $this->assertEquals(['cart.items_limit', 'cart', 'pdp'], $activeKeys);
    }
}
