<?php

declare(strict_types=1);

use Tests\Liip\Serializer\Fixtures\SerializerModel;

function deserialize_Tests_Liip_Serializer_Fixtures_SerializerModel(array $data): SerializerModel
{
    $model = new SerializerModel();
    $model->field = 'deserializer';

    return $model;
}
