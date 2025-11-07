<?php

declare(strict_types=1);

namespace Tests\Liip\Serializer\Fixtures;

class PrimitiveUnionTyping
{
    public int|string|false|float|array|null $property;
}
