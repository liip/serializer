<?php

declare(strict_types=1);

namespace Liip\Serializer;

use Liip\MetadataParser\Metadata\PropertyMetadata;
use Liip\MetadataParser\Metadata\PropertyTypeArray;
use Liip\MetadataParser\Metadata\PropertyTypeClass;

abstract class Recursion
{
    public static function check(string $className, array $stack, string $modelPath): bool
    {
        if (\array_key_exists($className, $stack) && $stack[$className] > 1) {
            throw new \Exception(sprintf('recursion for %s at %s', key($stack), $modelPath));
        }

        return false;
    }


    public static function hasMaxDepthReached(PropertyMetadata $propertyMetadata, array $stack): bool
    {
        if (null === $propertyMetadata->getMaxDepth()) {
            return false;
        }

        $className = self::getClassNameFromProperty($propertyMetadata);
        if (null === $className) {
            return false;
        }

        $classStackCount = $stack[$className] ?? 0;

        return $classStackCount > $propertyMetadata->getMaxDepth();
    }

    private static function getClassNameFromProperty(PropertyMetadata $propertyMetadata): ?string
    {
        $type = $propertyMetadata->getType();
        if ($type instanceof PropertyTypeArray) {
            $type = $type->getLeafType();
        }

        if (!($type instanceof PropertyTypeClass)) {
            return null;
        }

        return $type->getClassName();
    }
}
