<?php

use PHPUnit\Framework\TestCase;
use function Evolv\Utils\prune;

require_once __DIR__ . '/../../App/Utils/prune.php';


class PruneTest extends TestCase {
    /**
     * @test
     */
    public function shouldProduceKeysAndValuesFromObject()
    {
        // Arrange
        $obj = [
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
        $result = prune($obj, $active);

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

    /**
     * @test
     */
    public function shouldNotProduceKeysAndValuesFromObjectIfTheyDontExist() {
        // Arrange
        $obj = [
            'gods.zeus' => [
                'strength' => 123
            ]
        ];
        $active = ['titans.cronus'];

        // Act
        $result = prune($obj, $active);

        // Assert
        $this->assertEquals([], $result);
    }

    /**
     * @test
     */
    public function shouldProduceKeysAndValuesFromPredicatedObject() {
        // Arrange
        $obj = [
            'home' => [
                'cta_text' => [
                    '_predicated_values' => [
                        [
                            '_predicate' => [
                                'combinator' => 'and',
                                'rules' => [
                                    [
                                        'field' => 'device',
                                        'operator' => 'loose_equal',
                                        'value' => 'desktop'
                                    ]
                                ]
                            ],
                            '_predicate_assignment_id' => 'p1',
                            '_value' => 'This way to the PDP!'
                        ],
                        [
                            '_predicate' => null,
                            '_predicate_assignment_id' => 'group1-9cb1a685f141dfd048051a425ddb4657',
                            '_value' => 'Go To PDP'
                        ]
                    ],
                    '_predicated_variant_group_id' => 'group1'
                ]
            ],
            'pdp' => [
                'page_layout' => 'Layout 2'
            ]
        ];
        $active = ['home', 'home.cta_text', 'home.cta_text.p1'];

        // Act
        $result = prune($obj, $active);

        // Assert
        $this->assertEquals([
            'home' => [
                'cta_text' => 'This way to the PDP!'
            ],
            'home.cta_text' => 'This way to the PDP!'
        ], $result);
    }
}