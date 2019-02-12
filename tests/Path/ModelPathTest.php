<?php

declare(strict_types=1);

namespace Tests\Liip\Serializer\Path;

use Liip\Serializer\Path\ModelPath;
use PHPUnit\Framework\TestCase;

/**
 * @small
 */
class ModelPathTest extends TestCase
{
    public function testRoot(): void
    {
        $path = new ModelPath('model');

        $this->assertSame('$model', (string) $path);
    }

    public function testNested(): void
    {
        $path = new ModelPath('model');
        $path = $path->withPath('property1');
        $path = $path->withPath('property2');

        $this->assertSame('$model->property1->property2', (string) $path);
    }

    public function testArrayWithString(): void
    {
        $path = new ModelPath('model');
        $path = $path->withPath('property1');
        $path = $path->withArray('\'property2\'');

        $this->assertSame('$model->property1[\'property2\']', (string) $path);
    }

    public function testArrayWithVariable(): void
    {
        $path = new ModelPath('model');
        $path = $path->withPath('property1');
        $path = $path->withArray('$index');

        $this->assertSame('$model->property1[$index]', (string) $path);
    }

    public function testArrayNested(): void
    {
        $path = new ModelPath('model');
        $path = $path->withPath('property1');
        $path = $path->withArray('\'property2\'');
        $path = $path->withArray('\'property3\'');
        $path = $path->withPath('property4');

        $this->assertSame('$model->property1[\'property2\'][\'property3\']->property4', (string) $path);
    }
}
