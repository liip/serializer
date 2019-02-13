<?php

declare(strict_types=1);

namespace Tests\Liip\Serializer\Unit;

use JMS\Serializer\ArrayTransformerInterface;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializerInterface;
use Liip\Serializer\GeneratedSerializer;
use Liip\Serializer\SerializationContext;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Tests\Liip\Serializer\Fixtures\GeneratedSerializerFailureModel;
use Tests\Liip\Serializer\Fixtures\GeneratedSerializerModel;
use Tests\Liip\Serializer\Fixtures\PartialExclusionStrategy;

/**
 * @small
 */
class GeneratedSerializerTest extends TestCase
{
    /**
     * @var SerializerInterface|ArrayTransformerInterface|MockObject
     */
    private $jms;

    protected function setUp(): void
    {
        $this->jms = $this->createMock([SerializerInterface::class, ArrayTransformerInterface::class]);
    }

    public function testIncompleteConstructor(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Original serializer must implement both ArrayTransformerInterface and SerializerInterface');
        new GeneratedSerializer($this->createMock(SerializerInterface::class), '/tmp', new NullLogger());
    }

    public function testSerialize(): void
    {
        $transform = new GeneratedSerializer($this->jms, __DIR__.'/Fixtures', new NullLogger());

        $this->jms->expects($this->never())
            ->method('serialize')
        ;
        $this->jms->expects($this->never())
            ->method('toArray')
        ;

        $context = new SerializationContext();
        $context->setVersion(2);
        $context->setGroups(['api']);
        $json = $transform->serialize(new GeneratedSerializerModel(), 'json', $context);
        $this->assertSame('{"seen":true}', $json);
    }

    public function testSerializeFallbackOnFormat(): void
    {
        $transform = new GeneratedSerializer($this->jms, __DIR__.'/Fixtures', new NullLogger());
        $model = new GeneratedSerializerModel();
        $context = new SerializationContext();
        $context->setVersion(2);
        $context->setGroups(['api']);

        $this->jms->expects($this->once())
            ->method('serialize')
            ->with($model, 'xml', $context)
            ->will($this->returnValue('<it-worked/>'))
        ;
        $this->jms->expects($this->never())
            ->method('toArray')
        ;

        $xml = $transform->serialize($model, 'xml', $context);
        $this->assertSame('<it-worked/>', $xml);
    }

    public function testSerializeFallbackOnExclusionStrategy(): void
    {
        $transform = new GeneratedSerializer($this->jms, __DIR__.'/Fixtures', new NullLogger());
        $model = new GeneratedSerializerModel();
        $context = new SerializationContext();
        $context->addExclusionStrategy(new PartialExclusionStrategy(['field1', 'field2']));
        $context->setSerializeNull(true);

        $this->jms->expects($this->once())
            ->method('serialize')
            ->with($model, 'json', $context)
            ->will($this->returnValue('<it-worked/>'))
        ;
        $this->jms->expects($this->never())
            ->method('toArray')
        ;

        $xml = $transform->serialize($model, 'json', $context);
        $this->assertSame('<it-worked/>', $xml);
    }

    public function testSerializeEnabled(): void
    {
        $transform = new GeneratedSerializer($this->jms, __DIR__.'/Fixtures', new NullLogger(), [GeneratedSerializerModel::class]);

        $this->jms->expects($this->never())
            ->method('serialize')
        ;
        $this->jms->expects($this->never())
            ->method('toArray')
        ;

        $context = new SerializationContext();
        $context->setVersion(2);
        $context->setGroups(['api']);
        $json = $transform->serialize(new GeneratedSerializerModel(), 'json', $context);
        $this->assertSame('{"seen":true}', $json);
    }

    public function testSerializeNotEnabled(): void
    {
        $transform = new GeneratedSerializer($this->jms, __DIR__.'/Fixtures', new NullLogger(), []);
        $model = new GeneratedSerializerModel();
        $context = new SerializationContext();

        $this->jms->expects($this->once())
            ->method('serialize')
            ->with($model, 'json', $context)
            ->will($this->returnValue('<it-worked/>'))
        ;
        $this->jms->expects($this->never())
            ->method('toArray')
        ;

        $xml = $transform->serialize($model, 'json', $context);
        $this->assertSame('<it-worked/>', $xml);
    }

    public function testSerializeFallbackOnFileNotExists(): void
    {
        $transform = new GeneratedSerializer($this->jms, '/tmp/foo', new NullLogger());
        $model = new GeneratedSerializerModel();
        $context = new SerializationContext();
        $context->setVersion(2);
        $context->setGroups(['api']);

        $this->jms->expects($this->never())
            ->method('serialize')
        ;
        $this->jms->expects($this->once())
            ->method('toArray')
            ->with($model, $context)
            ->will($this->returnValue(['it' => 'worked']))
        ;

        $json = $transform->serialize($model, 'json', $context);
        $this->assertSame('{"it":"worked"}', $json);
    }

    public function testSerializeFallbackOnError(): void
    {
        $transform = new GeneratedSerializer($this->jms, __DIR__.'/Fixtures', new NullLogger());
        $model = new GeneratedSerializerFailureModel();
        $context = new SerializationContext();
        $context->setVersion(2);
        $context->setGroups(['api']);

        $this->jms->expects($this->never())
            ->method('serialize')
        ;
        $this->jms->expects($this->once())
            ->method('toArray')
            ->with($model, $context)
            ->will($this->returnValue(['it worked']))
        ;

        $json = $transform->serialize($model, 'json', $context);
        $this->assertSame('["it worked"]', $json);
    }

    public function testToArray(): void
    {
        $transform = new GeneratedSerializer($this->jms, __DIR__.'/Fixtures', new NullLogger());

        $this->jms->expects($this->never())
            ->method('toArray')
        ;

        $context = new SerializationContext();
        $context->setVersion(2);
        $context->setGroups(['api']);
        $json = $transform->toArray(new GeneratedSerializerModel(), $context);
        $this->assertSame(['seen' => true], $json);
    }

    public function testToArrayNotEnabled(): void
    {
        $transform = new GeneratedSerializer($this->jms, __DIR__.'/Fixtures', new NullLogger(), []);
        $model = new GeneratedSerializerModel();
        $context = new SerializationContext();

        $this->jms->expects($this->once())
            ->method('toArray')
            ->with($model, $context)
            ->will($this->returnValue(['fallback' => true]))
        ;

        $json = $transform->toArray($model, $context);
        $this->assertSame(['fallback' => true], $json);
    }

    public function testToArrayNoContext(): void
    {
        $transform = new GeneratedSerializer($this->jms, __DIR__.'/Fixtures', new NullLogger());

        $this->jms->expects($this->never())
            ->method('toArray')
        ;

        $json = $transform->toArray(new GeneratedSerializerModel());
        $this->assertSame(['all' => true], $json);
    }

    public function testDeserialize(): void
    {
        $transform = new GeneratedSerializer($this->jms, __DIR__.'/Fixtures', new NullLogger());

        $this->jms->expects($this->never())
            ->method('deserialize')
        ;
        $this->jms->expects($this->never())
            ->method('fromArray')
        ;

        $data = $transform->deserialize('[]', GeneratedSerializerModel::class, 'json');
        $this->assertInstanceOf(GeneratedSerializerModel::class, $data);
        $this->assertSame('deserializer', $data->field);
    }

    public function testDeserializeFallbackOnFormat(): void
    {
        $transform = new GeneratedSerializer($this->jms, __DIR__.'/Fixtures', new NullLogger());
        $model = new GeneratedSerializerModel();

        $this->jms->expects($this->once())
            ->method('deserialize')
            ->with('<xml-data/>', GeneratedSerializerModel::class, 'xml')
            ->will($this->returnValue($model))
        ;
        $this->jms->expects($this->never())
            ->method('fromArray')
        ;

        $data = $transform->deserialize('<xml-data/>', GeneratedSerializerModel::class, 'xml');
        $this->assertSame($model, $data);
    }

    public function testDeserializeNotEnabled(): void
    {
        $transform = new GeneratedSerializer($this->jms, __DIR__.'/Fixtures', new NullLogger(), []);
        $model = new GeneratedSerializerModel();

        $this->jms->expects($this->once())
            ->method('deserialize')
            ->with('<xml-data/>', GeneratedSerializerModel::class, 'json')
            ->will($this->returnValue($model))
        ;
        $this->jms->expects($this->never())
            ->method('fromArray')
        ;

        $data = $transform->deserialize('<xml-data/>', GeneratedSerializerModel::class, 'json');
        $this->assertSame($model, $data);
    }

    public function testDeserializeFallbackOnFileNotExists(): void
    {
        $transform = new GeneratedSerializer($this->jms, '/tmp/foo', new NullLogger());
        $model = new GeneratedSerializerModel();
        $context = new DeserializationContext();

        $this->jms->expects($this->never())
            ->method('deserialize')
        ;
        $this->jms->expects($this->once())
            ->method('fromArray')
            ->with(['it' => 'works'], GeneratedSerializerModel::class, $context)
            ->will($this->returnValue($model))
        ;

        $data = $transform->deserialize('{"it":"works"}', GeneratedSerializerModel::class, 'json', $context);
        $this->assertSame($model, $data);
    }

    public function testDeserializeFallbackOnError(): void
    {
        $transform = new GeneratedSerializer($this->jms, __DIR__.'/Fixtures', new NullLogger());
        $model = new GeneratedSerializerFailureModel();
        $context = new DeserializationContext();

        $this->jms->expects($this->once())
            ->method('fromArray')
            ->with(['it' => 'works'], GeneratedSerializerFailureModel::class, $context)
            ->will($this->returnValue($model))
        ;

        $data = $transform->deserialize('{"it":"works"}', GeneratedSerializerFailureModel::class, 'json', $context);
        $this->assertSame($model, $data);
    }

    public function testFromArrayFallbackOnVersion(): void
    {
        $transform = new GeneratedSerializer($this->jms, __DIR__.'/Fixtures', new NullLogger());
        $model = new GeneratedSerializerModel();
        $context = new DeserializationContext();
        $context->setVersion(2);
        $context->setGroups(['api']);

        $this->jms->expects($this->once())
            ->method('fromArray')
            ->with(['it' => 'works'], GeneratedSerializerModel::class, $context)
            ->will($this->returnValue($model))
        ;

        $data = $transform->fromArray(['it' => 'works'], GeneratedSerializerModel::class, $context);
        $this->assertSame($model, $data);
    }

    public function testFromArrayNotEnabled(): void
    {
        $transform = new GeneratedSerializer($this->jms, __DIR__.'/Fixtures', new NullLogger(), []);
        $model = new GeneratedSerializerModel();
        $context = new DeserializationContext();

        $this->jms->expects($this->once())
            ->method('fromArray')
            ->with(['it' => 'works'], GeneratedSerializerModel::class, $context)
            ->will($this->returnValue($model))
        ;

        $data = $transform->fromArray(['it' => 'works'], GeneratedSerializerModel::class, $context);
        $this->assertSame($model, $data);
    }

    public function testFromArrayNoContext(): void
    {
        $transform = new GeneratedSerializer($this->jms, __DIR__.'/Fixtures', new NullLogger());

        $this->jms->expects($this->never())
            ->method('fromArray')
        ;

        $data = $transform->fromArray([], GeneratedSerializerModel::class);
        $this->assertInstanceOf(GeneratedSerializerModel::class, $data);
        $this->assertSame('deserializer', $data->field);
    }
}
