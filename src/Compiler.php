<?php

declare(strict_types=1);

namespace Liip\Serializer;

use Liip\MetadataParser\Builder;

final class Compiler
{
    /**
     * @var Builder
     */
    private $metadataBuilder;

    /**
     * @var DeserializerGenerator
     */
    private $deserializerGenerator;

    /**
     * @var SerializerGenerator
     */
    private $serializerGenerator;

    public function __construct(
        Builder $metadataBuilder,
        DeserializerGenerator $deserializerGenerator,
        SerializerGenerator $serializerGenerator
    ) {
        $this->metadataBuilder = $metadataBuilder;
        $this->deserializerGenerator = $deserializerGenerator;
        $this->serializerGenerator = $serializerGenerator;
    }

    public function compile(): void
    {
        $this->serializerGenerator->generate($this->metadataBuilder);
        $this->deserializerGenerator->generate($this->metadataBuilder);
    }
}
