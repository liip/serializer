<?php

declare(strict_types=1);

namespace Tests\Liip\Serializer\Fixtures;

use JMS\Serializer\AbstractVisitor;
use JMS\Serializer\Context;
use JMS\Serializer\Exclusion\ExclusionStrategyInterface;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;

/**
 * Exclusion strategy for JMS serializer to only include fields which are part of the partial update.
 */
class PartialExclusionStrategy implements ExclusionStrategyInterface
{
    /**
     * @var string[]
     */
    private $fields = [];

    /**
     * @var string[]
     */
    private $nestedFields = [];

    /**
     * @param string[] $fields
     */
    public function __construct(array $fields)
    {
        $this->fields = $fields;
    }

    /**
     * Sets the nested fields which will be serialized deeper.
     *
     * @param string[] $fields
     */
    public function setNestedFields(array $fields): void
    {
        $this->nestedFields = $fields;
    }

    public function shouldSkipProperty(PropertyMetadata $property, Context $context)
    {
        if (0 === \count($this->fields)) {
            // Don't skip fields for full import
            return false;
        }

        $visitor = $context->getVisitor();
        $name = $property->serializedName;
        if (null === $name && $visitor instanceof AbstractVisitor) {
            $name = $visitor->getNamingStrategy()->translateName($property);
        } else {
            $name = $property->name;
        }

        $depth = $context->getDepth();

        // Products contain other products within the variants.
        // if we're within variants (depth >= 2) the depth should start one level up again because if we want to
        // import regional_information partially, we want to import it at the base product as well as within the variants.
        if ($depth > 1 && $this->isPartOfNestedField($context)) {
            --$depth;
        }

        // if depth is > 1 we can safely serialize this because partial import allows to only specify fields on the first
        // level of a Product (normalized based on variants, see above).
        // Example: you can partial import `retailer` but not `retailer.id`. If `retailer` (level 1) is partially imported,
        // then `retailer.id` (level 2) is required to be in the serializer output.
        if ($depth > 1) {
            return false;
        }

        // at level 1 is `variants` and if that's in the nested fields we should always serialize it.
        if (1 === $depth && $this->isNestedField($name)) {
            return false;
        }

        // in the end, only serialize fields within the partially imported fields.
        return !\in_array($name, $this->fields, true);
    }

    /**
     * @return string[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @return string[]
     */
    public function getNestedFields(): array
    {
        return $this->nestedFields;
    }

    public function shouldSkipClass(ClassMetadata $metadata, Context $context)
    {
        return false;
    }

    private function isNestedField(string $name): bool
    {
        return \in_array($name, $this->nestedFields, true);
    }

    private function isPartOfNestedField(Context $context): bool
    {
        $path = $context->getCurrentPath();

        foreach ($this->nestedFields as $nestedField) {
            if (\in_array($nestedField, $path, true)) {
                return true;
            }
        }

        return false;
    }
}
