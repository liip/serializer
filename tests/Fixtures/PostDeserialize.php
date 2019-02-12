<?php

declare(strict_types=1);

namespace Tests\Liip\Serializer\Fixtures;

use JMS\Serializer\Annotation as Serializer;

class PostDeserialize
{
    /**
     * @Serializer\Type("string")
     */
    public $apiString;

    /**
     * @Serializer\Exclude
     */
    public $postCalled;

    /**
     * @Serializer\PostDeserialize
     */
    public function postDeserialize(): void
    {
        $this->postCalled = 'post has been called';
    }
}
