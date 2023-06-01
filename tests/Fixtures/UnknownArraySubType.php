<?php

declare(strict_types=1);

namespace Tests\Liip\Serializer\Fixtures;

use JMS\Serializer\Annotation as Serializer;

class UnknownArraySubType
{
    /**
     * @var array
     *
     * @Serializer\Type("array")
     */
    public $unknownSubType;
}
