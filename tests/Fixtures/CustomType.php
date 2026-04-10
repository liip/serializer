<?php

declare(strict_types=1);

namespace Tests\Liip\Serializer\Fixtures;

class CustomType
{
    public string $key = 'static value';

    public function __construct(public readonly string $value)
    {
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }
}
