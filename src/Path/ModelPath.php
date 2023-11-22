<?php

declare(strict_types=1);

namespace Liip\Serializer\Path;

/**
 * Representation of a model path in PHP, e.g. $model->property1[$index]->property2, used for code generation.
 */
final class ModelPath implements \Stringable
{
    /**
     * @var AbstractEntry[]
     */
    private array $path = [];

    public function __construct(string $root)
    {
        $this->path = [new Root($root)];
    }

    public function __toString(): string
    {
        return implode('', $this->path);
    }

    /**
     * @param string[] $components
     */
    public static function tempVariable(array $components): self
    {
        $components = array_map(
            static fn (string $component): string => ucfirst(str_replace(['->', '[', ']', '$'], '', $component)),
            $components
        );

        return new self(lcfirst(implode('', $components)));
    }

    public static function indexVariable(string $path): self
    {
        return new self('index'.mb_strlen($path));
    }

    public function withPath(string $component): self
    {
        $clone = clone $this;
        $clone->path[] = new ModelEntry($component);

        return $clone;
    }

    public function withArray(string $component): self
    {
        $clone = clone $this;
        $clone->path[] = new ArrayEntry($component);

        return $clone;
    }
}
