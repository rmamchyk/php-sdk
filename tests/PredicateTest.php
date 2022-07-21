<?php

use PHPUnit\Framework\TestCase;

use App\Predicate;

require_once __DIR__ . '/../App/Predicate.php';


class PredicateTest extends TestCase {
    protected Predicate $predicate;

    public function setUp(): void {
        $this->predicate = new Predicate();
    }

    /**
     * @test
     */
    public function shouldEvaluateFlatPredicateCorrectly() {
        // Arrange
        $predicate = [
            'id' => 123,
            'combinator' => 'and',
            'rules' => [
                0 => [
                    'field' => 'web.referrer',
                    'operator' => 'exists',
                    'value' => null,
                    'index' => 0
                ],
                1 => [
                    'field' => 'platform',
                    'operator' => 'equal',
                    'value' => 'ios',
                    'index' => 1
                ]
            ]
        ];
        $context = [
            'web' => [
                'referrer' => 'http://stackoverflow.com/'
            ],
            'platform' => 'ios'
        ];

        // Act
        $result = $this->predicate->evaluate($context, $predicate);

        // Assert
        $this->assertCount(2, $result['passed']);
        $this->assertCount(0, $result['failed']);
    }
}
