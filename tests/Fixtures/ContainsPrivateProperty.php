<?php

declare(strict_types=1);

namespace Tests\Liip\Serializer\Fixtures;

use JMS\Serializer\Annotation as Serializer;

/**
 * Model that contains another model that has a getter. Getter must not be called when child is null.
 */
class ContainsPrivateProperty
{
    /**
     * @Serializer\Type("Tests\Liip\Serializer\Fixtures\PrivateProperty")
     *
     * @var PrivateProperty
     */
    public $child;

    /**
     * @Serializer\Type("string")
     * @Serializer\Groups({"api"})
     */
    public $apiString;
}
