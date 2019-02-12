<?php

declare(strict_types=1);

namespace Liip\Serializer\Path;

class ModelEntry extends AbstractEntry
{
    public function __toString(): string
    {
        return '->'.$this->getPath();
    }
}
