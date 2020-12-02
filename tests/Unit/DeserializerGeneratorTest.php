<?php

declare(strict_types=1);

namespace Tests\Liip\Serializer\Unit;

use Doctrine\Common\Annotations\AnnotationReader;
use Liip\MetadataParser\Builder;
use Liip\MetadataParser\ModelParser\JMSParser;
use Liip\MetadataParser\ModelParser\PhpDocParser;
use Liip\MetadataParser\ModelParser\ReflectionParser;
use Liip\Serializer\DeserializerGenerator;
use Liip\Serializer\Template\Deserialization;
use Tests\Liip\Serializer\Fixtures\ContainsNonEmptyConstructor;
use Tests\Liip\Serializer\Fixtures\FloatProperty;
use Tests\Liip\Serializer\Fixtures\Inheritance;
use Tests\Liip\Serializer\Fixtures\ListModel;
use Tests\Liip\Serializer\Fixtures\Model;
use Tests\Liip\Serializer\Fixtures\Nested;
use Tests\Liip\Serializer\Fixtures\NonEmptyConstructor;
use Tests\Liip\Serializer\Fixtures\PostDeserialize;
use Tests\Liip\Serializer\Fixtures\PrivateProperty;
use Tests\Liip\Serializer\Fixtures\VirtualProperties;

/**
 * @medium
 */
class DeserializerGeneratorTest extends SerializerTestCase
{
    /**
     * @var Builder
     */
    private static $metadataBuilder;

    public static function setUpBeforeClass(): void
    {
        static::$metadataBuilder = self::createMetadataBuilder([
            new ReflectionParser(),
            new PhpDocParser(),
            new JMSParser(new AnnotationReader()),
        ]);
    }

    public function testNested(): void
    {
        $functionName = 'deserialize_Tests_Liip_Serializer_Fixtures_Model';
        self::generateDeserializer(self::$metadataBuilder, Model::class, $functionName);

        $input = [
            'api_string' => 'api',
            'detail_string' => 'details',
            'nested_field' => ['nested_string' => 'nested'],
            'date' => '2018-08-03T00:00:00+02:00',
            'date_with_format' => '2018-08-04',
            'date_immutable' => '2016-06-01T00:00:00+02:00',
        ];

        /** @var Model $model */
        $model = $functionName($input);
        static::assertInstanceOf(Model::class, $model);
        static::assertSame('api', $model->apiString);
        static::assertSame('details', $model->detailString);
        static::assertNull($model->unAnnotated);
        static::assertInstanceOf(Nested::class, $model->nestedField);
        static::assertSame('nested', $model->nestedField->nestedString);
        static::assertInstanceOf(\DateTime::class, $model->date);
        static::assertSame('2018-08-03', $model->date->format('Y-m-d'));
        static::assertInstanceOf(\DateTime::class, $model->dateWithFormat);
        static::assertSame('2018-08-04', $model->dateWithFormat->format('Y-m-d'));
        static::assertInstanceOf(\DateTimeImmutable::class, $model->dateImmutable);
        static::assertSame('2016-06-01', $model->dateImmutable->format('Y-m-d'));
    }

    public function testLists(): void
    {
        $functionName = 'deserialize_Tests_Liip_Serializer_Fixtures_ListModel';
        self::generateDeserializer(self::$metadataBuilder, ListModel::class, $functionName);

        $input = [
            'array' => ['a', 'b'],
            'list_nested' => [
                ['nested_string' => 'nested1'],
                ['nested_string' => 'nested2'],
            ],
        ];

        /** @var ListModel $model */
        $model = $functionName($input);
        static::assertInstanceOf(ListModel::class, $model);
        static::assertSame(['a', 'b'], $model->array);
        static::assertIsArray($model->listNested);
        static::assertCount(2, $model->listNested);
        foreach ($model->listNested as $index => $nested) {
            static::assertInstanceOf(Nested::class, $nested);
            static::assertSame('nested'.($index + 1), $nested->nestedString);
        }
    }

    /**
     * JSON has no type information and if no .0 is used, integers and floats can be confused.
     */
    public function testFloatProperty(): void
    {
        $functionName = 'deserialize_Tests_Liip_Serializer_Fixtures_FloatProperty';
        self::generateDeserializer(self::$metadataBuilder, FloatProperty::class, $functionName);

        $input = [
            'number' => 1,
        ];

        /** @var FloatProperty $model */
        $model = $functionName($input);
        static::assertInstanceOf(FloatProperty::class, $model);
        static::assertSame(1.0, $model->number);
    }

    public function testPrivateProperty(): void
    {
        $functionName = 'deserialize_Tests_Liip_Serializer_Fixtures_PrivateProperty';
        self::generateDeserializer(self::$metadataBuilder, PrivateProperty::class, $functionName);

        $input = [
            'extra' => 'More Info',
            'api_string' => 'apiString',
        ];

        /** @var PrivateProperty $model */
        $model = $functionName($input);
        static::assertInstanceOf(PrivateProperty::class, $model);
        static::assertSame('apiString_setter', $model->getApiString());
        static::assertSame('More Info', $model->getExtra());
    }

    public function testInheritance(): void
    {
        $functionName = 'deserialize_Tests_Liip_Serializer_Fixtures_Inheritance';
        self::generateDeserializer(self::$metadataBuilder, Inheritance::class, $functionName);

        $input = [
            'foo' => 'More Info',
            'api_string' => 'apiString',
        ];

        /** @var Inheritance $model */
        $model = $functionName($input);
        static::assertInstanceOf(Inheritance::class, $model);
        static::assertSame('apiString_setter', $model->getApiString());
        static::assertSame('More Info', $model->getExtra());
    }

    public function testNonEmptyConstructorRoot(): void
    {
        $templating = new Deserialization();
        $deserializerGenerator = new DeserializerGenerator($templating, [NonEmptyConstructor::class], '/tmp');

        $this->expectExceptionMessage('We currently do not support deserializing when the root class has a non-empty constructor');
        $deserializerGenerator->generate(static::$metadataBuilder);
    }

    public function testNonEmptyConstructor(): void
    {
        $functionName = 'deserialize_Tests_Liip_Serializer_Fixtures_ContainsNonEmptyConstructor';
        self::generateDeserializer(self::$metadataBuilder, ContainsNonEmptyConstructor::class, $functionName);

        $input = [
            'child' => [
                'api_string' => 'apiString',
            ],
        ];

        /** @var ContainsNonEmptyConstructor $model */
        $model = $functionName($input);
        static::assertInstanceOf(ContainsNonEmptyConstructor::class, $model);
        $child = $model->child;
        static::assertInstanceOf(NonEmptyConstructor::class, $child);
        static::assertSame('apiString', $child->getApiString());
        static::assertSame('optional', $child->getOptional());
        static::assertSame(['foo', 'bar'], $child->getOnlyArgument());

        $input = [
            'child' => [
                'api_string' => 'apiString',
                'optional' => 'custom',
            ],
        ];
        /** @var ContainsNonEmptyConstructor $model */
        $model = $functionName($input);
        static::assertInstanceOf(ContainsNonEmptyConstructor::class, $model);
        $child = $model->child;
        static::assertInstanceOf(NonEmptyConstructor::class, $child);
        static::assertSame('apiString', $child->getApiString());
        static::assertSame('custom', $child->getOptional());
        static::assertSame(['foo', 'bar'], $child->getOnlyArgument());
    }

    public function testVirtualProperties(): void
    {
        $functionName = 'deserialize_Tests_Liip_Serializer_Fixtures_VirtualProperties';
        self::generateDeserializer(self::$metadataBuilder, VirtualProperties::class, $functionName);

        $input = [
            'api_string' => 'apiString',
            'api_string_virtual' => 'nopenopenope',
        ];

        /** @var VirtualProperties $model */
        $model = $functionName($input);
        static::assertInstanceOf(VirtualProperties::class, $model);
        static::assertSame('apiString', $model->apiString);
    }

    public function testPostDeserialize(): void
    {
        $functionName = 'deserialize_Tests_Liip_Serializer_Fixtures_PostDeserialize';
        self::generateDeserializer(self::$metadataBuilder, PostDeserialize::class, $functionName);

        $input = [
            'api_string' => 'apiString',
        ];

        /** @var PostDeserialize $model */
        $model = $functionName($input);
        static::assertInstanceOf(PostDeserialize::class, $model);
        static::assertSame('apiString', $model->apiString);
        static::assertSame('post has been called', $model->postCalled);
    }
}
