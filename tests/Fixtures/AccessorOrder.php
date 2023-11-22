<?php

declare(strict_types=1);

namespace Tests\Liip\Serializer\Fixtures;

use JMS\Serializer\Annotation as Serializer;

/**
 * Without this the total hits would be in the bottom of the results, we want them on top.
 *
 * We need to add both field names and method names to support accessor methods too.
 *
 * @Serializer\AccessorOrder("custom", custom={"totalHits", "getTotalHits", "apiString2", "getApiString2", "notExisting", "apiString1"})
 */
class AccessorOrder
{
    /**
     * @Serializer\Type("string")
     *
     * @var string
     */
    public $apiString1;

    public function __construct(
        /**
         * @Serializer\Type("integer")
         *
         * @Serializer\Until("1")
         */
        public ?int $totalHits,
        string $apiString1,
        /**
         * @Serializer\Type("string")
         *
         * @Serializer\Until("1")
         */
        public ?string $apiString2
    ) {
        $this->apiString1 = $apiString1;
    }

    /**
     * @Serializer\Type("string")
     *
     * @Serializer\VirtualProperty
     *
     * @Serializer\SerializedName("api_string2")
     *
     * @Serializer\Since("2")
     */
    public function getApiString2(): string
    {
        return $this->apiString2;
    }

    /**
     * @Serializer\Type("integer")
     *
     * @Serializer\VirtualProperty
     *
     * @Serializer\SerializedName("total_hits")
     *
     * @Serializer\Since("2")
     */
    public function getTotalHits(): int
    {
        return $this->totalHits;
    }
}
