<?php

declare(strict_types=1);

namespace Tests\Liip\Serializer\Fixtures;

use JMS\Serializer\Annotation as Serializer;

class RecursionModel
{
    /**
     * @Serializer\Type("string")
     */
    public $property;

    /**
     * @Serializer\MaxDepth(2)
     *
     * @Serializer\Type("Tests\Liip\Serializer\Fixtures\RecursionModel")
     */
    public $recursion;
}
