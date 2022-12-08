<?php

use PHPUnit\Framework\TestCase;
use function Evolv\Utils\regexFromString;

require_once __DIR__ . '/../../App/Utils/regexFromString.php';

class RegexFromStringTest extends TestCase {
    private function verify(string $input) {
        // Act
        $result = regexFromString($input);

        // Assert
        $this->assertEquals('/http:\/\/.*\/path/', $result);
    }

    /**
     * @test
     */
    public function shouldEncloseRegexWithSlashesIfOmitted() {
        // Arrange
        $input = 'http:\/\/.*\/path';

        $this->verify($input);
    }

    /**
     * @test
     */
    public function shouldEscapeAllSlashesWithinRegex() {
        // Arrange
        $input = '/http://.*/path/';

        $this->verify($input);
    }

    /**
     * @test
     */
    public function shouldSkipEscapingAlreadyEscapedSlashes() {
        // Arrange
        $input = '/http:\/\/.*/path/';

        $this->verify($input);
    }
}

