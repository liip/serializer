<?php

declare(strict_types=1);

namespace Liip\Serializer\Configuration;

/**
 * @implements \IteratorAggregate<int, GroupCombination>
 */
class ClassToGenerate implements \IteratorAggregate
{
    /**
     * A list of group combinations, potentially with a version overwrite.
     *
     * @see GroupCombination::$groups
     *
     * @var GroupCombination[]
     */
    private array $groupCombinations = [];

    /**
     * Overwrite global default list of versions to generate.
     *
     * @see GroupCombination::$versions
     *
     * @var list<string>|null
     */
    private ?array $defaultVersions;

    /**
     * @param class-string          $className
     * @param list<string|int>|null $defaultVersions
     */
    public function __construct(
        private GeneratorConfiguration $configuration,
        private string $className,
        ?array $defaultVersions = null
    ) {
        $this->defaultVersions = null === $defaultVersions ? null : array_map('strval', $defaultVersions);
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * @return list<string>
     */
    public function getDefaultVersions(): array
    {
        if (null !== $this->defaultVersions) {
            return $this->defaultVersions;
        }

        return $this->configuration->getDefaultVersions();
    }

    public function addGroupCombination(GroupCombination $groupCombination): void
    {
        $this->groupCombinations[] = $groupCombination;
    }

    public function getIterator(): \Traversable
    {
        if ($this->groupCombinations) {
            return new \ArrayIterator($this->groupCombinations);
        }

        return new \ArrayIterator($this->configuration->getDefaultGroupCombinations($this));
    }
}
