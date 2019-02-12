<?php

declare(strict_types=1);

namespace Tests\Liip\Serializer\Fixtures;

use JMS\Serializer\Annotation as Serializer;

/**
 * Our generator must fail on this class because there is neither a constructor with the field nor a setter, and no getter.
 */
class InaccessiblePrivateProperty
{
    /**
     * @Serializer\Type("string")
     */
    private $apiString;
}
