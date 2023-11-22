<?php

declare(strict_types=1);

namespace Tests\Liip\Serializer\Fixtures;

use JMS\Serializer\Annotation as Serializer;

class PrimitiveArraySubType
{
    /**
     * @Serializer\Type("array<int, array<int>>")
     */
    public array $primitivesLists = [];
}
