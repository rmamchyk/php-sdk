<?php

use PHPUnit\Framework\TestCase;
use function App\Utils\setKeyToValue;

require_once __DIR__ . '/../../App/Utils/setKeyToValue.php';


class SetKeyToValueTest extends TestCase {
    public function testNewKeyIsAddedToRoot() {
        //Arrange
        $array = [];

        // Act
        setKeyToValue('native', 15, $array);

        // Assert
        $this->assertEquals($array, ['native' => 15]);
    }

    public function testNewKeyIsAddedToNestedArray() {
        //Arrange
        $array = ['native' => ['pdp' => ['page_layout' => 'Layout 1']]];

        // Act
        setKeyToValue('native.pdp.extra_key', true, $array);

        // Assert
        $this->assertEquals($array, [
            'native' =>
                ['pdp' => [
                    'page_layout' => 'Layout 1',
                    'extra_key' => true
                ]]
            ]);
    }
}