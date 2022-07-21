<?php

use PHPUnit\Framework\TestCase;
use function App\Utils\removeValueForKey;

require_once __DIR__ . '/../../App/Utils/removeValueForKey.php';


class RemoveValueForKeyTest extends TestCase {
    /**
     * @test
    */
    public function shouldRemoveKeyFromTheRoot() {
        //Arrange
        $array = ['native' => true];

        // Act
        $removed = removeValueForKey('native', $array);

        // Assert
        $this->assertTrue($removed);
        $this->assertEmpty($array);
    }

    /**
     * @test
    */
    public function shouldRemoveKeyFromNestedArray() {
        //Arrange
        $array = [
            'native' => [
                'pdp' => [
                    'page_layout' => 'Layout 1',
                    'extra_key' => true
                ]
            ]
        ];

        // Act
        $removed = removeValueForKey('native.pdp.extra_key', $array);

        // Assert
        $this->assertTrue($removed);
        $this->assertEquals($array, [
            'native' => [
                'pdp' => [
                    'page_layout' => 'Layout 1'
                ]
            ]
        ]);
    }

    /**
     * @test
    */
    public function shouldNotRemoveKeyIfItDoesNotExists() {
        //Arrange
        $array = [
            'native' => [
                'pdp' => [
                    'page_layout' => 'Layout 1',
                    'extra_key' => true
                ]
            ]
        ];

        // Act
        $removed = removeValueForKey('native.unknown.extra_key', $array);

        // Assert
        $this->assertFalse($removed);
        $this->assertEquals($array, [
            'native' => [
                'pdp' => [
                    'page_layout' => 'Layout 1',
                    'extra_key' => true
                ]
            ]
        ]);
    }

    /**
     * @test
    */
    public function shouldRemoveParentKeyIfItDoesNotHaveAnyChildKeys() {
        //Arrange
        $array = [
            'native' => [
                'pdp' => [
                    'page_layout' => 'Layout 1'
                ]
            ]
        ];

        // Act
        $removed = removeValueForKey('native.pdp.page_layout', $array);

        // Assert
        $this->assertTrue($removed);
        $this->assertEmpty($array);
    }
}