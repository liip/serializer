<?php

declare(strict_types=1);

namespace Liip\Serializer\Path;

/**
 * Representation of an array path in PHP, e.g. $data['property1'][$index]['property2'], used for code generation.
 */
final class ArrayPath
{
    /**
     * @var AbstractEntry[]
     */
    private $path = [];

    public function __construct(string $root)
    {
        $this->path = [new Root($root)];
    }

    public function __toString(): string
    {
        return implode('', $this->path);
    }

    public function withFieldName(string $component): self
    {
        $clone = clone $this;
        $clone->path[] = new ArrayEntry('\''.$component.'\'');

        return $clone;
    }

    public function withVariable(string $component): self
    {
        $clone = clone $this;
        $clone->path[] = new ArrayEntry($component);

        return $clone;
    }
}
