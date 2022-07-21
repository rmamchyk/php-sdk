<?php

use PHPUnit\Framework\TestCase;
use function App\Utils\getValueForKey;

require_once __DIR__ . '/../../App/Utils/getValueForKey.php';


class GetValueForKeyTest extends TestCase {
    public function testNullIsReturnedWhenEmptyArray() {
        // Act
        $value = getValueForKey('native', []);

        // Assert
        $this->assertNull($value);
    }

    public function testValueIsReturnedForRootKey() {
        // Act
        $value = getValueForKey('native', ['native' => 15]);

        // Assert
        $this->assertEquals($value, 15);
    }

    public function testValueIsReturnedForNestedKey() {
        // Act
        $value = getValueForKey('native.newUser', ['native' => ['newUser' => true]]);

        // Assert
        $this->assertEquals($value, true);
    }

    public function testFoundValueCanBeOfArrayType() {
        // Act
        $value = getValueForKey('native.newUser', ['native' => ['newUser' => ['name' => 'John']]]);

        // Assert
        $this->assertEquals($value, ['name' => 'John']);
    }
}