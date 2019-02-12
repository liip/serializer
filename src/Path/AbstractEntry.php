<?php

declare(strict_types=1);

namespace Liip\Serializer\Path;

abstract class AbstractEntry
{
    /**
     * @var string
     */
    private $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    abstract public function __toString(): string;

    protected function getPath(): string
    {
        return $this->path;
    }
}
