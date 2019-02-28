<?php

declare(strict_types=1);

namespace Tests\Liip\Serializer\Fixtures;

use JMS\Serializer\Annotation as Serializer;

class FloatProperty
{
    /**
     * @Serializer\Type("float")
     */
    public $number;
}
