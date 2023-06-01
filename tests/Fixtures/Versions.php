<?php

declare(strict_types=1);

namespace Tests\Liip\Serializer\Fixtures;

use JMS\Serializer\Annotation as Serializer;

class Versions
{
    /**
     * @Serializer\Type("string")
     *
     * @Serializer\Until("2")
     */
    public $old;

    /**
     * @Serializer\Type("string")
     *
     * @Serializer\Until("2")
     */
    public $changed;

    /**
     * @Serializer\Type("string")
     *
     * @Serializer\Since("3")
     */
    public $new;

    /**
     * @Serializer\Type("string")
     *
     * @Serializer\Since("3")
     *
     * @Serializer\VirtualProperty
     *
     * @Serializer\SerializedName("changed")
     */
    public function getChangedInV3()
    {
        return mb_strtoupper($this->changed);
    }
}
