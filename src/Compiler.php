<?php

declare(strict_types=1);

namespace Liip\Serializer;

use Liip\MetadataParser\Builder;

final class Compiler
{
    public function __construct(
        private Builder $metadataBuilder,
        private DeserializerGenerator $deserializerGenerator,
        private SerializerGenerator $serializerGenerator
    ) {
    }

    public function compile(): void
    {
        $this->serializerGenerator->generate($this->metadataBuilder);
        $this->deserializerGenerator->generate($this->metadataBuilder);
    }
}
