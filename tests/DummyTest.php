<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;

class DummyTest extends TestCase
{
    public function testDummyAssertion(): void
    {
        $this->assertTrue(true);
        $this->assertEquals(2, 1 + 1);
        $this->assertIsString('hello');
    }

    public function testPhpVersion(): void
    {
        $this->assertGreaterThanOrEqual('8.0', PHP_VERSION);
    }

    public function testArrayOperations(): void
    {
        $array = [1, 2, 3];
        $this->assertCount(3, $array);
        $this->assertContains(2, $array);
    }
}