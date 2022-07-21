<?php

use PHPUnit\Framework\TestCase;
use function App\Utils\prune;

require_once __DIR__ . '/../../App/Utils/prune.php';


class PruneTest extends TestCase {
    /**
     * @test
     */
    public function shouldProduceKeysAndValuesFromObject()
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
        $active = ['gods.zeus', 'gods.apollo', 'goddesses.athena'];

        // Act
        $result = prune($array, $active);

        // Assert
        $this->assertEquals([
            'gods.zeus' => [
                'strength' => 123
            ],
            'gods.apollo' => [
                'powers' => [
                    'flight' => true
                ]
            ],
            'goddesses.athena' => [
                'strength' => 456
            ]
        ], $result);
    }
}