<?php

declare(strict_types=1);

namespace Tests\Liip\Serializer\Unit\Path;

use Liip\Serializer\Context;
use PHPUnit\Framework\TestCase;

/**
 * @small
 */
class ContextTest extends TestCase
{
    public function testDuplicateGroups(): void
    {
        $context = new Context();
        $context->setGroups(['a', 'b', 'a']);
        static::assertSame(['a', 'b'], $context->getGroups());
    }
}
