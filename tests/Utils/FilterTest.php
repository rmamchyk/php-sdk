<?php

use PHPUnit\Framework\TestCase;
use function App\Utils\filter;

require_once __DIR__ . '/../../App/Utils/filter.php';


class FilterTest extends TestCase {
    /**
     * @test
     */
    public function shouldProduceFilteredArrayBasedUponKeys()
    {
        // Arrange
        $array = [
            'gods' => [
                'zeus' => [
                    'strength' => 123
                ],
                'apollo' => [
                    'powers' => [
                        'flight' => true
                    ]
                ]
            ],
            'goddesses' => [
                'athena' => [
                    'strength' => 456
                ]
            ]
        ];
        $active = ['gods.zeus', 'goddesses.athena'];

        // Act
        $result = filter($array, $active);

        // Assert
        $this->assertEquals([
            'gods' => [
                'zeus' => [
                    'strength' => 123
                ]
            ],
            'goddesses' => [
                'athena' => [
                    'strength' => 456
                ]
            ]
        ], $result);
    }
}