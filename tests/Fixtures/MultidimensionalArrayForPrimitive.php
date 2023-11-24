<?php

declare(strict_types=1);

namespace Tests\Liip\Serializer\Fixtures;

use JMS\Serializer\Annotation as Serializer;

class MultidimensionalArrayForPrimitive
{
    /**
     * @Serializer\Type("array<int, array<int>>")
     */
    public array $twoDims = [];

    /**
     * @Serializer\Type("array<int, array<int, array<int, array<int, array<int>>>>>")
     */
    public array $fiveDims = [];

    /**
     * @Serializer\Type("array<string, array<int>>")
     */
    public array $mapOfLists = [];

    /**
     * @Serializer\Type("array<int, array<string, array<int>>>")
     */
    public array $listOfMapOfLists = [];
}
