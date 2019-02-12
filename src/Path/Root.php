<?php

declare(strict_types=1);

namespace Liip\Serializer\Path;

final class Root extends AbstractEntry
{
    public function __toString(): string
    {
        return '$'.$this->getPath();
    }
}
