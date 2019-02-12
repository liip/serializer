<?php

declare(strict_types=1);

namespace Liip\Serializer;

use JMS\Serializer\ContextFactory\SerializationContextFactoryInterface;

class SerializationContextFactory implements SerializationContextFactoryInterface
{
    public function createSerializationContext()
    {
        return new SerializationContext();
    }
}
