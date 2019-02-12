<?php

declare(strict_types=1);

namespace Tests\Liip\Serializer;

use Liip\Serializer\SerializationContext;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Liip\Serializer\Fixtures\PartialExclusionStrategy;

/**
 * @small
 */
class SerializationContextTest extends KernelTestCase
{
    public function testCustomExclusionStrategy(): void
    {
        $context = SerializationContext::create();

        $context->addExclusionStrategy(new PartialExclusionStrategy(['field1']));

        $this->assertTrue($context->hasCustomExclusionStrategy());
    }

    public function testNoCustomExclusionStrategy(): void
    {
        $context = SerializationContext::create();

        $context->setVersion(3);

        $this->assertFalse($context->hasCustomExclusionStrategy());
    }

    public function testNoExclusionStrategy(): void
    {
        $context = SerializationContext::create();

        $this->assertFalse($context->hasCustomExclusionStrategy());
    }
}
