<?php

declare(strict_types=1);

namespace Tests\Liip\Serializer\Fixtures;

use Doctrine\Common\Collections\Collection;
use JMS\Serializer\Annotation as Serializer;

class ListModel
{
    /**
     * @Serializer\Type("array<string>")
     */
    public $array;

    /**
     * @Serializer\Type("array<string, Tests\Liip\Serializer\Fixtures\Nested>")
     */
    public $hashmap;

    /**
     * @var Nested[]
     *
     * @Serializer\Type("array<Tests\Liip\Serializer\Fixtures\Nested>")
     */
    public $listNested;

    /**
     * @var Nested[]
     *
     * @Serializer\Type("array<Tests\Liip\Serializer\Fixtures\Nested>")
     * @Serializer\Accessor("getOptionalList")
     */
    public $optionalList;

    /**
     * @var string[]|Collection|null
     *
     * @Serializer\Type("ArrayCollection<string>")
     */
    public $collection;

    /**
     * @var Nested[string]|Collection|null
     *
     * @Serializer\Type("ArrayCollection<string, Tests\Liip\Serializer\Fixtures\Nested>")
     */
    public $collectionNested;

    public function getOptionalList()
    {
        return $this->optionalList ?: null;
    }
}
