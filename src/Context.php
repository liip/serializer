<?php

declare(strict_types=1);

namespace Liip\Serializer;

/**
 * Container for context information: version and group selection.
 */
final class Context
{
    /**
     * @var string
     */
    private $version;

    /**
     * @var string[]
     */
    private $groups = [];

    public function __construct()
    {
    }

    /**
     * @return string[]
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * @param string[] $groups
     */
    public function setGroups(array $groups): self
    {
        $this->groups = array_unique($groups);

        return $this;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(string $version): self
    {
        $this->version = $version;

        return $this;
    }
}
