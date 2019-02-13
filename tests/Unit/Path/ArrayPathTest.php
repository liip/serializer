<?php

declare(strict_types=1);

namespace Tests\Liip\Serializer\Unit\Path;

use Liip\Serializer\Path\ArrayPath;
use PHPUnit\Framework\TestCase;

/**
 * @small
 */
class ArrayPathTest extends TestCase
{
    public function testRoot(): void
    {
        $path = new ArrayPath('data');

        $this->assertSame('$data', (string) $path);
    }

    public function testNested(): void
    {
        $path = new ArrayPath('data');
        $path = $path->withFieldName('property1');
        $path = $path->withFieldName('property2');

        $this->assertSame('$data[\'property1\'][\'property2\']', (string) $path);
    }

    public function testNestedAsVariable(): void
    {
        $path = new ArrayPath('data');
        $path = $path->withVariable('$property1');
        $path = $path->withVariable('$property2');

        $this->assertSame('$data[$property1][$property2]', (string) $path);
    }
}
