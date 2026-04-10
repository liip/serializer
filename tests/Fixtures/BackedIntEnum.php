<?php

declare(strict_types=1);

namespace Tests\Liip\Serializer\Fixtures;

enum BackedIntEnum: int
{
    case Low = 1;
    case High = 2;
}
