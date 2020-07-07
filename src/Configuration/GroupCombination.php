<?php

declare(strict_types=1);

namespace Liip\Serializer\Configuration;

class GroupCombination
{
    /**
     * @var ClassToGenerate
     */
    private $containingClass;

    /**
     * One combination of groups to generate.
     *
     * An empty array means to generate with no groups.
     * e.g. ['api', 'details'].
     *
     * @var string[]
     */
    private $groups;

    /**
     * List of versions to generate.
     *
     * An empty string '' means to generate without a version.
     * e.g. ['', '2', '3']
     *
     * If not specified, this falls back to the class default.
     * If the array is not null, it must have a length > 0.
     *
     * @var string[]|null
     */
    private $versions;

    public function __construct(ClassToGenerate $containingClass, array $groups, ?array $versions = null)
    {
        $this->containingClass = $containingClass;
        $this->groups = $groups;
        if (null !== $versions && 0 === \count($versions)) {
            throw new \InvalidArgumentException('Version list may not be empty. To generate without version, specify an empty string. To use the default versions, pass null.');
        }
        $this->versions = $versions;
    }

    /**
     * @return string[]
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * @return string[]
     */
    public function getVersions(): array
    {
        if (null !== $this->versions) {
            return $this->versions;
        }

        return $this->containingClass->getDefaultVersions();
    }
}
