<?php

declare(strict_types=1);

namespace Liip\Serializer;

/**
 * Container for context information: version and group selection.
 */
final class Context
{
    private ?string $version = null;

    /**
     * @var list<string>
     */
    private array $groups = [];

    public function __construct()
    {
    }

    /**
     * @return list<string>
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
        $this->groups = array_values(array_unique($groups));
        sort($this->groups);

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
