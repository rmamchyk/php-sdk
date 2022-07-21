<?php

use PHPUnit\Framework\TestCase;
use function App\Utils\expand;

require_once __DIR__ . '/../../App/Utils/expand.php';


class ExpandTest extends TestCase {
    /**
     * @test
     */
    public function shouldCorrectlyExpandFlattenedArray()
    {
        // Arrange
        $array = [
            'level1.level2.level3.value0' => false,
            'level1.level2.value1' => 1,
            'value2' => 'value2'
        ];

        // Act
        $result = expand($array);

        print_r($result);

        // Assert
        $this->assertCount(2, array_keys($result));
        $this->assertEquals([
            'level1' => [
                'level2' => [
                    'level3' => [
                        'value0' => false
                    ],
                    'value1' => 1
                ]
            ],
            'value2' => 'value2'
        ], $result);
    }
}