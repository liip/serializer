<?php

declare(strict_types=1);

namespace Tests\Liip\Serializer\Unit;

use Liip\Serializer\Context;
use Liip\Serializer\Exception\Exception;
use Liip\Serializer\Exception\UnsupportedFormatException;
use Liip\Serializer\Exception\UnsupportedTypeException;
use Liip\Serializer\Serializer;
use PHPUnit\Framework\TestCase;
use Tests\Liip\Serializer\Fixtures\GeneratedSerializerFailureModel;
use Tests\Liip\Serializer\Fixtures\GeneratedSerializerModel;

/**
 * @small
 */
class SerializerTest extends TestCase
{
    public function testSerialize(): void
    {
        $transform = new Serializer(__DIR__.'/../Fixtures');

        $context = new Context();
        $context->setVersion('2');
        $context->setGroups(['api']);
        $json = $transform->serialize(new GeneratedSerializerModel(), 'json', $context);
        $this->assertSame('{"seen":true}', $json);
    }

    public function testSerializeFailOnFormat(): void
    {
        $transform = new Serializer(__DIR__.'/../Fixtures');
        $model = new GeneratedSerializerModel();
        $context = new Context();
        $context->setVersion('2');
        $context->setGroups(['api']);

        $this->expectException(UnsupportedFormatException::class);
        $transform->serialize($model, 'xml', $context);
    }

    public function testSerializeFailOnFileNotExists(): void
    {
        $transform = new Serializer('/tmp/foo');
        $model = new GeneratedSerializerModel();
        $context = new Context();
        $context->setVersion('2');
        $context->setGroups(['api']);

        $this->expectException(UnsupportedTypeException::class);
        $transform->serialize($model, 'json', $context);
    }

    public function testSerializeFailOnError(): void
    {
        $transform = new Serializer(__DIR__.'/../Fixtures');
        $model = new GeneratedSerializerFailureModel();
        $context = new Context();
        $context->setVersion('2');
        $context->setGroups(['api']);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Error during serialization');
        $transform->serialize($model, 'json', $context);
    }

    public function testToArray(): void
    {
        $transform = new Serializer(__DIR__.'/../Fixtures');

        $context = new Context();
        $context->setVersion('2');
        $context->setGroups(['api']);
        $json = $transform->toArray(new GeneratedSerializerModel(), $context);
        $this->assertSame(['seen' => true], $json);
    }

    public function testToArrayNoContext(): void
    {
        $transform = new Serializer(__DIR__.'/../Fixtures');

        $json = $transform->toArray(new GeneratedSerializerModel());
        $this->assertSame(['all' => true], $json);
    }

    public function testDeserialize(): void
    {
        $transform = new Serializer(__DIR__.'/../Fixtures');

        $data = $transform->deserialize('[]', GeneratedSerializerModel::class, 'json');
        $this->assertInstanceOf(GeneratedSerializerModel::class, $data);
        $this->assertSame('deserializer', $data->field);
    }

    public function testDeserializeFailOnFormat(): void
    {
        $transform = new Serializer(__DIR__.'/../Fixtures');

        $this->expectException(UnsupportedFormatException::class);
        $transform->deserialize('<xml-data/>', GeneratedSerializerModel::class, 'xml');
    }

    public function testDeserializeFailOnFileNotExists(): void
    {
        $transform = new Serializer('/tmp/foo');

        $this->expectException(UnsupportedTypeException::class);
        $transform->deserialize('{"it":"works"}', GeneratedSerializerModel::class, 'json');
    }

    public function testDeserializeFailOnError(): void
    {
        $transform = new Serializer(__DIR__.'/../Fixtures');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Error during deserialization');
        $transform->deserialize('{"it":"fails horribly"}', GeneratedSerializerFailureModel::class, 'json');
    }

    public function testFromArrayFailOnVersion(): void
    {
        $transform = new Serializer(__DIR__.'/../Fixtures');
        $context = new Context();
        $context->setVersion('2');
        $context->setGroups(['api']);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Version and group support is not implemented for deserialization');
        $transform->fromArray(['it' => 'works'], GeneratedSerializerModel::class, $context);
    }

    public function testFromArrayNoContext(): void
    {
        $transform = new Serializer(__DIR__.'/../Fixtures');

        $data = $transform->fromArray([], GeneratedSerializerModel::class);
        $this->assertInstanceOf(GeneratedSerializerModel::class, $data);
        $this->assertSame('deserializer', $data->field);
    }
}
