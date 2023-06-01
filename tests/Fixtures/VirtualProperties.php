<?php

declare(strict_types=1);

namespace Tests\Liip\Serializer\Fixtures;

use JMS\Serializer\Annotation as Serializer;

class VirtualProperties
{
    /**
     * @Serializer\Type("string")
     */
    public $apiString;

    /**
     * @Serializer\Type("string")
     *
     * @Serializer\VirtualProperty
     *
     * @Serializer\SerializedName("api_string_virtual")
     */
    public function getApiString()
    {
        return $this->apiString.'_virtual';
    }
}
