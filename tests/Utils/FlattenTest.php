<?php

use PHPUnit\Framework\TestCase;
use function Evolv\Utils\flatten;

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

    /**
     * @test
     */
    public function shouldCorrectlyFlattenNestedList() {
        // Arrange
        $array = [
            'experiments' => [
                'confirmations' => [
                    [
                        'cid' => '7da22c6c3ad8:b3b78ae95f',
                        'timestamp' => 1670578648
                    ]
                ]
            ]
        ];

        // Act
        $result = flatten($array);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals($result, [
            'experiments.confirmations' => [
                [
                    'cid' => '7da22c6c3ad8:b3b78ae95f',
                    'timestamp' => 1670578648
                ]
            ]
        ]);
    }
}
