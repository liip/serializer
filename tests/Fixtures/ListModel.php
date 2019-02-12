<?php

declare(strict_types=1);

namespace Tests\Liip\Serializer\Fixtures;

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

    public function getOptionalList()
    {
        return $this->optionalList ?: null;
    }
}
