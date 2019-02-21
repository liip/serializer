<?php

declare(strict_types=1);

function deserialize_Tests_Liip_Serializer_Fixtures_SerializerModel(array $data)
{
    $model = new Tests\Liip\Serializer\Fixtures\SerializerModel();
    $model->field = 'deserializer';

    return $model;
}
