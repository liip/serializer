<?php

declare(strict_types=1);

namespace Liip\Serializer\Configuration;

class ClassToGenerate implements \IteratorAggregate
{
    /**
     * @var GeneratorConfiguration
     */
    private $configuration;

    /**
     * A list of group combinations, potentially with a version overwrite.
     *
     * @see GroupCombination::$groups
     *
     * @var GroupCombination[]
     */
    private $groupCombinations = [];

    /**
     * Fully qualified class name.
     *
     * @var string
     */
    private $className;

    /**
     * Overwrite global default list of versions to generate.
     *
     * @see GroupCombination::$versions
     *
     * @var string[]|null
     */
    private $defaultVersions;

    public function __construct(GeneratorConfiguration $configuration, string $className, ?array $defaultVersions = null)
    {
        $this->configuration = $configuration;
        $this->className = $className;
        $this->defaultVersions = null === $defaultVersions ? null : array_map('strval', $defaultVersions);
    }

    public function getClassName(): string
    {
        return $this->className;
    }

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

    public function getIterator()
    {
        if ($this->groupCombinations) {
            return new \ArrayIterator($this->groupCombinations);
        }

        return new \ArrayIterator($this->configuration->getDefaultGroupCombinations($this));
    }
}
