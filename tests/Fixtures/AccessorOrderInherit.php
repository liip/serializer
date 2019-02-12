<?php

declare(strict_types=1);

namespace Tests\Liip\Serializer\Fixtures;

use JMS\Serializer\Annotation as Serializer;

/**
 * Accessor order must be inherited from parent class if not overwritten.
 */
class AccessorOrderInherit extends AccessorOrder
{
    /**
     * @var string
     * @Serializer\Type("string")
     */
    public $apiString0;

    public function __construct(int $totalHits, string $apiString1, string $apiString2, string $apiString0)
    {
        parent::__construct($totalHits, $apiString1, $apiString2);
        $this->apiString0 = $apiString0;
    }
}
