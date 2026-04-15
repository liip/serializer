<?php

declare(strict_types=1);

namespace Tests\Liip\Serializer\Fixtures;

use JMS\Serializer\Annotation as Serializer;

class ModelWithCustomType
{
    public ?CustomType $value = null;

    #[Serializer\Type('array<'.CustomType::class.'>')]
    public ?array $values = null;
}
