<?php

use PHPUnit\Framework\TestCase;
use function App\Utils\flatten;

require_once __DIR__ . '/../../App/Utils/flatten.php';


class FlattenTest extends TestCase {
    /**
     * @test
     */
    public function shouldCorrectlyFlattenNestedArray() {
        // Arrange
        $array = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'value0' => false
                    ],
                    'value1' => 1
                ]
            ],
            'value2' => 'value2'
        ];

        // Act
        $result = flatten($array);

        // Assert
        $this->assertCount(3, $result);
        $this->assertEquals($result, [
            'level1.level2.level3.value0' => false,
            'level1.level2.value1' => 1,
            'value2' => 'value2'
        ]);
    }
}