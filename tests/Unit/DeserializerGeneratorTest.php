<?php

declare(strict_types=1);

namespace Tests\Liip\Serializer\Unit;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Collections\ArrayCollection;
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
use Tests\Liip\Serializer\Fixtures\RecursionModel;
use Tests\Liip\Serializer\Fixtures\UnknownArraySubType;
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
        self::assertInstanceOf(Model::class, $model);
        self::assertSame('api', $model->apiString);
        self::assertSame('details', $model->detailString);
        self::assertNull($model->unAnnotated);
        self::assertInstanceOf(Nested::class, $model->nestedField);
        self::assertSame('nested', $model->nestedField->nestedString);
        self::assertInstanceOf(\DateTime::class, $model->date);
        self::assertSame('2018-08-03', $model->date->format('Y-m-d'));
        self::assertInstanceOf(\DateTime::class, $model->dateWithFormat);
        self::assertSame('2018-08-04', $model->dateWithFormat->format('Y-m-d'));
        self::assertInstanceOf(\DateTimeImmutable::class, $model->dateImmutable);
        self::assertSame('2016-06-01', $model->dateImmutable->format('Y-m-d'));
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
            'collection' => ['entry', 'second entry'],
            'collection_nested' => [
                'first' => ['nested_string' => 'nested3'],
                'second' => ['nested_string' => 'nested4'],
            ],
        ];

        /** @var ListModel $model */
        $model = $functionName($input);
        self::assertInstanceOf(ListModel::class, $model);
        self::assertSame(['a', 'b'], $model->array);
        self::assertIsArray($model->listNested);
        self::assertCount(2, $model->listNested);

        foreach ($model->listNested as $index => $nested) {
            self::assertInstanceOf(Nested::class, $nested);
            self::assertSame('nested'.($index + 1), $nested->nestedString);
        }

        self::assertInstanceOf(ArrayCollection::class, $model->collection);
        self::assertCount(2, $model->collection);
        self::assertSame(['entry', 'second entry'], $model->collection->toArray());

        self::assertInstanceOf(ArrayCollection::class, $model->collectionNested);
        self::assertCount(2, $model->collectionNested);
        self::assertArrayHasKey('first', $model->collectionNested);
        self::assertSame('nested3', $model->collectionNested['first']->nestedString);

        self::assertArrayHasKey('second', $model->collectionNested);
        self::assertSame('nested4', $model->collectionNested['second']->nestedString);
    }

    public function testRecursion(): void
    {
        $functionName = 'deserialize_Tests_Liip_Serializer_Fixtures_RecursionModel';
        self::generateDeserializer(self::$metadataBuilder, RecursionModel::class, $functionName);

        $input = [
            'property' => 'property',
            'recursion' => [
                'property' => 'recursive property',
                'recursion' => [
                    'property' => 'recursive recursive property',
                    'recursion' => [
                        'property' => 'recursive recursive recursive property',
                    ],
                ],
            ],
        ];

        /** @var RecursionModel $model */
        $model = $functionName($input);
        self::assertInstanceOf(RecursionModel::class, $model);
        self::assertSame('property', $model->property);
        self::assertInstanceOf(RecursionModel::class, $model->recursion);

        self::assertSame('recursive property', $model->recursion->property);
        self::assertInstanceOf(RecursionModel::class, $model->recursion->recursion);

        self::assertSame('recursive recursive property', $model->recursion->recursion->property);
        self::assertNull($model->recursion->recursion->recursion);
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
        self::assertInstanceOf(FloatProperty::class, $model);
        self::assertSame(1.0, $model->number);
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
        self::assertInstanceOf(PrivateProperty::class, $model);
        self::assertSame('apiString_setter', $model->getApiString());
        self::assertSame('More Info', $model->getExtra());
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
        self::assertInstanceOf(Inheritance::class, $model);
        self::assertSame('apiString_setter', $model->getApiString());
        self::assertSame('More Info', $model->getExtra());
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
        self::assertInstanceOf(ContainsNonEmptyConstructor::class, $model);
        $child = $model->child;
        self::assertInstanceOf(NonEmptyConstructor::class, $child);
        self::assertSame('apiString', $child->getApiString());
        self::assertSame('optional', $child->getOptional());
        self::assertSame(['foo', 'bar'], $child->getOnlyArgument());

        $input = [
            'child' => [
                'api_string' => 'apiString',
                'optional' => 'custom',
            ],
        ];
        /** @var ContainsNonEmptyConstructor $model */
        $model = $functionName($input);
        self::assertInstanceOf(ContainsNonEmptyConstructor::class, $model);
        $child = $model->child;
        self::assertInstanceOf(NonEmptyConstructor::class, $child);
        self::assertSame('apiString', $child->getApiString());
        self::assertSame('custom', $child->getOptional());
        self::assertSame(['foo', 'bar'], $child->getOnlyArgument());
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
        self::assertInstanceOf(VirtualProperties::class, $model);
        self::assertSame('apiString', $model->apiString);
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
        self::assertInstanceOf(PostDeserialize::class, $model);
        self::assertSame('apiString', $model->apiString);
        self::assertSame('post has been called', $model->postCalled);
    }

    public function testArraysWithUnknownSubType(): void
    {
        $functionName = 'deserialize_Tests_Liip_Serializer_Fixtures_UnknownArraySubType';
        self::generateDeserializer(self::$metadataBuilder, UnknownArraySubType::class, $functionName, ['allow_generic_arrays' => true]);

        $unknownSubtype = ['unknown' => 'type', 'nested' => ['unknown' => 'subtype']];
        $input = [
            'unknown_sub_type' => $unknownSubtype,
        ];

        $list = new UnknownArraySubType();
        $list->unknownSubType = $unknownSubtype;

        $model = $functionName($input);
        self::assertInstanceOf(UnknownArraySubType::class, $model);
        self::assertSame($unknownSubtype, $list->unknownSubType);
    }
}
