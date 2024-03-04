<?php

declare(strict_types=1);

namespace Tests\Liip\Serializer\Fixtures;

use JMS\Serializer\Annotation as Serializer;

class Model
{
    /**
     * @Serializer\Type("string")
     *
     * @Serializer\Groups({"api"})
     */
    public $apiString;

    /**
     * @Serializer\Type("string")
     *
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
     * @Serializer\Type("DateTime<'Y-m-d', '', 'd/m/Y'>")
     *
     * @var \DateTime
     */
    public $dateWithOneDeserializationFormat;

    /**
     * @Serializer\Type("DateTime<'Y-m-d', '', ['m/d/Y', 'Y-m-d']>")
     *
     * @var \DateTime
     */
    public $dateWithMultipleDeserializationFormats;

    /**
     * @Serializer\Type("DateTime<'Y-m-d', '+0600', '!d/m/Y'>")
     *
     * @var \DateTime
     */
    public $dateWithTimezone;

    /**
     * @Serializer\Type("DateTimeImmutable")
     *
     * @var \DateTimeImmutable
     */
    public $dateImmutable;

    /**
     * @Serializer\Type("DateTimeImmutable")
     *
     * @Serializer\Accessor(getter="getDateImmutablePrivate", setter="setDateImmutablePrivate")
     */
    private $dateImmutablePrivate;

    public function getDateImmutablePrivate()
    {
        return $this->dateImmutablePrivate;
    }

    public function setDateImmutablePrivate($dateImmutablePrivate): void
    {
        $this->dateImmutablePrivate = $dateImmutablePrivate;
    }
}
