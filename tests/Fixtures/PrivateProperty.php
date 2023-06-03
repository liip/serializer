<?php

declare(strict_types=1);

namespace Tests\Liip\Serializer\Fixtures;

use JMS\Serializer\Annotation as Serializer;

class PrivateProperty
{
    /**
     * @Serializer\Type("string")
     *
     * @Serializer\Accessor(getter="getExtra", setter="setExtra")
     */
    protected $extra;

    /**
     * @Serializer\Type("string")
     *
     * @Serializer\Accessor(getter="getApiString", setter="setApiString")
     */
    private $apiString;

    public function getExtra()
    {
        return $this->extra;
    }

    public function setExtra($extra): void
    {
        $this->extra = $extra;
    }

    public function getApiString()
    {
        return $this->apiString;
    }

    public function setApiString(string $apiString): void
    {
        $this->apiString = $apiString.'_setter';
    }
}
