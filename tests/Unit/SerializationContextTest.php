<?php

declare(strict_types=1);

namespace Tests\Liip\Serializer\Unit;

use Liip\Serializer\Context;
use PHPUnit\Framework\TestCase;

/**
 * @small
 */
class SerializationContextTest extends TestCase
{
    public function testEmpty(): void
    {
        $context = new Context();
        static::assertNull($context->getVersion());
        static::assertCount(0, $context->getGroups());
    }

    public function testSetVersion(): void
    {
        $context = new Context();
        $context->setVersion('3');
        static::assertSame('3', $context->getVersion());
    }

    public function testSetGroups(): void
    {
        $context = new Context();
        $context->setGroups(['a', 'b', 'c']);

        static::assertSame(['a', 'b', 'c'], $context->getGroups());
    }
}
