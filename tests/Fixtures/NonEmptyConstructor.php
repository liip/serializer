<?php

declare(strict_types=1);

namespace Tests\Liip\Serializer\Fixtures;

use JMS\Serializer\Annotation as Serializer;

class NonEmptyConstructor
{
    private const FOOBAR = 'bar';

    /**
     * @Serializer\Type("string")
     */
    private $apiString;

    /**
     * @Serializer\Exclude
     */
    private $onlyArgument;

    /**
     * @Serializer\Type("string")
     */
    private $optional;

    public function __construct(string $apiString, array $onlyArgument = ['foo', self::FOOBAR], string $optional = 'optional')
    {
        $this->apiString = $apiString;
        $this->onlyArgument = $onlyArgument;
        $this->optional = $optional;
    }

    public function getApiString()
    {
        return $this->apiString;
    }

    public function getOnlyArgument(): array
    {
        return $this->onlyArgument;
    }

    public function getOptional()
    {
        return $this->optional;
    }
}
