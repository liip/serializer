<?php

declare(strict_types=1);

namespace Tests\Liip\Serializer\Fixtures;

use JMS\Serializer\Annotation as JMS;

/**
 * @JMS\Discriminator(field = "type", map = {
 *     "first": "Tests\Liip\Serializer\Fixtures\DiscriminatorFirstChild",
 *     "second": "Tests\Liip\Serializer\Fixtures\DiscriminatorSecondChild"
 * })
 */
abstract class Discriminator
{
}
