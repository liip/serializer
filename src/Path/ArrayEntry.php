<?php

declare(strict_types=1);

namespace Liip\Serializer\Path;

final class ArrayEntry extends AbstractEntry
{
    public function __toString(): string
    {
        return '['.$this->getPath().']';
    }
}
