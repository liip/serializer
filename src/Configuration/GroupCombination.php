<?php

declare(strict_types=1);

namespace Liip\Serializer\Configuration;

class GroupCombination
{
    /**
     * @param list<string>|null $versions
     */
    public function __construct(
        private ClassToGenerate $containingClass,
        /**
         * @var list<string> One combination of groups to generate.
         *                   An empty array means to generate with no groups.
         *                   e.g. ['api', 'details'].
         */
        private array $groups,

        /**
         * List of versions to generate.
         *
         * An empty string '' means to generate without a version.
         * e.g. ['', '2', '3']
         *
         * If not specified, this falls back to the class default.
         * If the array is not null, it must have a length > 0.
         */
        private ?array $versions = null
    ) {
        sort($this->groups);

        if (null !== $versions && 0 === \count($versions)) {
            throw new \InvalidArgumentException('Version list may not be empty. To generate without version, specify an empty string. To use the default versions, pass null.');
        }
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
