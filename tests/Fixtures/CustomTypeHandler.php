<?php

declare(strict_types=1);

namespace Tests\Liip\Serializer\Fixtures;

use Liip\Serializer\DeserializerHandlerInterface;
use Liip\Serializer\SerializerHandlerInterface;

class CustomTypeHandler implements SerializerHandlerInterface, DeserializerHandlerInterface
{
    public function generateSerializeExpression(string $className, string $modelPath): string
    {
        return $modelPath.'->getValue()';
    }

    public function generateDeserializeExpression(string $className, string $arrayPath): string
    {
        return '\\'.CustomType::class.'::fromString('.$arrayPath.')';
    }

    public function getDeserializationClasses(): array
    {
        return [CustomType::class];
    }

    public function getSerializationClasses(): array
    {
        return [CustomType::class];
    }
}
