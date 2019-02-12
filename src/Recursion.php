<?php

declare(strict_types=1);

namespace Liip\Serializer;

abstract class Recursion
{
    public static function check(string $className, array $stack, string $modelPath): bool
    {
        if (\array_key_exists($className, $stack) && $stack[$className] > 1) {
            throw new \Exception(sprintf('recursion for %s at %s', key($stack), $modelPath));
        }

        return false;
    }
}
