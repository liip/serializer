<?php

declare(strict_types=1);

namespace Tests\Liip\Serializer\Fixtures;

use JMS\Serializer\Annotation\UnionDiscriminator;

class ComplexUnionTyping
{
    #[UnionDiscriminator(field: 'objectType', map: ['comment' => DiscriminatorComment::class, 'author' => DiscriminatorAuthor::class])]
    public DiscriminatorComment|DiscriminatorAuthor $property;
}
