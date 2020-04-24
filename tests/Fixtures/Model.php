<?php

declare(strict_types=1);

namespace Tests\Liip\Serializer\Fixtures;

use JMS\Serializer\Annotation as Serializer;

class Model
{
    /**
     * @Serializer\Type("string")
     * @Serializer\Groups({"api"})
     */
    public $apiString;

    /**
     * @Serializer\Type("string")
     * @Serializer\Groups({"details"})
     */
    public $detailString;

    public $unAnnotated;

    /**
     * @var Nested
     *
     * @Serializer\Type("Tests\Liip\Serializer\Fixtures\Nested")
     */
    public $nestedField;

    /**
     * @Serializer\Type("DateTime")
     *
     * @var \DateTime
     */
    public $date;

    /**
     * @Serializer\Type("DateTime<'Y-m-d'>")
     *
     * @var \DateTime
     */
    public $dateWithFormat;

    /**
     * @Serializer\Type("DateTimeImmutable")
     *
     * @var \DateTimeImmutable
     */
    public $dateImmutable;
}
