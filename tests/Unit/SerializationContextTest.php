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
        self::assertNull($context->getVersion());
        self::assertCount(0, $context->getGroups());
    }

    public function testSetVersion(): void
    {
        $context = new Context();
        $context->setVersion('3');
        self::assertSame('3', $context->getVersion());
    }

    public function testSetGroups(): void
    {
        $context = new Context();
        $context->setGroups(['a', 'b', 'c']);

        self::assertSame(['a', 'b', 'c'], $context->getGroups());
    }
}
