<?php

declare(strict_types=1);

namespace Tests\Liip\Serializer\Fixtures;

use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;

class DiscriminatorComment
{
    public string $text;

    #[Type(name: 'string')]
    #[SerializedName(name: 'objectType')]
    public $objectType = 'comment';

    public function getObjectType(): string
    {
        return $this->objectType;
    }
}
