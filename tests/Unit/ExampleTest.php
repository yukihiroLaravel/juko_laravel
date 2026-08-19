<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    public function test_テストの実行環境が動作する(): void
    {
        // Arrange
        $expected = true;

        // Act
        $actual = true;

        // Assert
        $this->assertSame($expected, $actual);
    }
}
