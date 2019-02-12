<?php

declare(strict_types=1);

namespace Tests\Liip\Serializer\Fixtures;

use JMS\Serializer\Annotation as Serializer;

class ContainsNonEmptyConstructor
{
    /**
     * @Serializer\Type("Tests\Liip\Serializer\Fixtures\NonEmptyConstructor")
     *
     * @var NonEmptyConstructor
     */
    public $child;
}
