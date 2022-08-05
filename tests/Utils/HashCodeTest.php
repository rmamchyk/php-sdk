<?php

use PHPUnit\Framework\TestCase;
use function Evolv\Utils\hashCode;

require_once __DIR__ . '/../../App/Utils/hashCode.php';


class HashCodeTest extends TestCase {
    protected string $string1;

    public function setUp(): void {
        $this->string1 = json_encode([
            'id' => 123,
            'type' => 'hybrid',
            'disabled' => false,
            'value' => 'console.log("HELLO")'
        ]);
    }

    /**
     * @test
     */
    public function shouldProduceConsistentHashForStringifiedJSONObject()
    {
        // Act
        $value = hashCode($this->string1);

        // Assert
        $this->assertEquals(1414479601, $value);
    }

    /**
     * @test
     */
    public function shouldProduceDifferentHashForTwoDifferentStringifiedJSONObjects()
    {
        // Arrange
        $string2 = json_encode([
            'id' => 123,
            'type' => 'hybrid',
            'disabled' => false,
            'value' => 'console.log("HI")'
        ]);

        // Act
        $value1 = hashCode($this->string1);
        $value2 = hashCode($string2);

        // Assert
        $this->assertNotEquals($value1, $value2);
        $this->assertEquals(1414479601, $value1);
        $this->assertEquals(1283140696, $value2);
    }
}