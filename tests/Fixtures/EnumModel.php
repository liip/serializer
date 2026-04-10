<?php

declare(strict_types=1);

namespace Tests\Liip\Serializer\Fixtures;

use JMS\Serializer\Annotation as Serializer;

class EnumModel
{
    #[Serializer\Type('enum<'.BackedStringEnum::class.'>')]
    public ?BackedStringEnum $backedString = null;

    public ?BackedStringEnum $backedStringWithoutAttribute = null;

    #[Serializer\Type('enum<'.BackedStringEnum::class.", 'name'>")]
    public ?BackedStringEnum $backedStringAsName = null;

    #[Serializer\Type('enum<'.BackedIntEnum::class.'>')]
    public ?BackedIntEnum $backedInt = null;

    #[Serializer\Type('enum<'.UnitEnum::class.'>')]
    public ?UnitEnum $unit = null;

    #[Serializer\Type('array<enum<'.BackedStringEnum::class.'>>')]
    public ?array $backedStringArray = null;
}
