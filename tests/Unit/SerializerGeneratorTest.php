<?php

declare(strict_types=1);

namespace Tests\Liip\Serializer\Unit;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation\UnionDiscriminator;
use Liip\MetadataParser\Builder;
use Liip\MetadataParser\ModelParser\JMSParser;
use Liip\MetadataParser\ModelParser\PhpDocParser;
use Liip\MetadataParser\ModelParser\ReflectionParser;
use Tests\Liip\Serializer\Fixtures\AccessorOrder;
use Tests\Liip\Serializer\Fixtures\AccessorOrderInherit;
use Tests\Liip\Serializer\Fixtures\BackedIntEnum;
use Tests\Liip\Serializer\Fixtures\BackedStringEnum;
use Tests\Liip\Serializer\Fixtures\ComplexUnionTyping;
use Tests\Liip\Serializer\Fixtures\ContainsPrivateProperty;
use Tests\Liip\Serializer\Fixtures\CustomType;
use Tests\Liip\Serializer\Fixtures\CustomTypeHandler;
use Tests\Liip\Serializer\Fixtures\DiscriminatorAuthor;
use Tests\Liip\Serializer\Fixtures\DiscriminatorComment;
use Tests\Liip\Serializer\Fixtures\DiscriminatorDependency;
use Tests\Liip\Serializer\Fixtures\DiscriminatorFirstChild;
use Tests\Liip\Serializer\Fixtures\EnumModel;
use Tests\Liip\Serializer\Fixtures\InaccessiblePrivateProperty;
use Tests\Liip\Serializer\Fixtures\Inheritance;
use Tests\Liip\Serializer\Fixtures\ListModel;
use Tests\Liip\Serializer\Fixtures\Model;
use Tests\Liip\Serializer\Fixtures\ModelWithCustomType;
use Tests\Liip\Serializer\Fixtures\MultidimensionalArrayForPrimitive;
use Tests\Liip\Serializer\Fixtures\Nested;
use Tests\Liip\Serializer\Fixtures\PostDeserialize;
use Tests\Liip\Serializer\Fixtures\PrimitiveUnionTyping;
use Tests\Liip\Serializer\Fixtures\PrivateProperty;
use Tests\Liip\Serializer\Fixtures\RecursionModel;
use Tests\Liip\Serializer\Fixtures\UnitEnum;
use Tests\Liip\Serializer\Fixtures\UnknownArraySubType;
use Tests\Liip\Serializer\Fixtures\Versions;
use Tests\Liip\Serializer\Fixtures\VirtualProperties;

/**
 * @medium
 */
class SerializerGeneratorTest extends SerializerTestCase
{
    private static Builder $metadataBuilder;

    public static function setUpBeforeClass(): void
    {
        static::$metadataBuilder = self::createMetadataBuilder([
            new ReflectionParser(),
            new PhpDocParser(),
            new JMSParser(new AnnotationReader()),
        ]);
    }

    public function testGroups(): void
    {
        $functionNoGroups = 'serialize_Tests_Liip_Serializer_Fixtures_Model_2';
        $functionApi = 'serialize_Tests_Liip_Serializer_Fixtures_Model_api_2';
        $functionApiDetails = 'serialize_Tests_Liip_Serializer_Fixtures_Model_api_details_2';
        $groups = [
            [],
            ['api'],
            ['details', 'api'],
        ];
        self::generateSerializers(self::$metadataBuilder, Model::class, [$functionNoGroups, $functionApi, $functionApiDetails], ['2'], $groups);

        $model = new Model();
        $model->apiString = 'api';
        $model->detailString = 'details';
        $model->unAnnotated = 'unAnnotated';
        $model->nestedField = new Nested('nested');
        $model->date = new \DateTime('2018-08-03', new \DateTimeZone('Europe/Zurich'));
        $model->dateImmutable = new \DateTimeImmutable('2016-06-01', new \DateTimeZone('Europe/Zurich'));

        $expected = [
            'api_string' => 'api',
            'detail_string' => 'details',
            'un_annotated' => 'unAnnotated',
            'nested_field' => ['nested_string' => 'nested'],
            'date' => '2018-08-03T00:00:00+0200',
            'date_immutable' => '2016-06-01T00:00:00+0200',
        ];
        $data = $functionNoGroups($model);
        self::assertSame($expected, $data, 'no groups specified');

        $expected = [
            'api_string' => 'api',
        ];
        $data = $functionApi($model);
        self::assertSame($expected, $data, 'group api');

        $expected = [
            'api_string' => 'api',
            'detail_string' => 'details',
        ];
        $data = $functionApiDetails($model);
        self::assertSame($expected, $data, 'groups api and details');
    }

    public function testArrays(): void
    {
        $functionName = 'serialize_Tests_Liip_Serializer_Fixtures_ListModel';
        self::generateSerializers(self::$metadataBuilder, ListModel::class, [$functionName], ['']);

        $list = new ListModel();
        $list->array = ['a', 'b'];
        $list->listNested = [
            new Nested('nested1'),
            new Nested('nested2'),
        ];
        $list->optionalList = [
            new Nested('opt1'),
            new Nested('opt2'),
        ];
        $list->collection = new ArrayCollection(['a', 'b']);
        $list->collectionNested = new ArrayCollection(['a' => new Nested('nested1'), 'b' => new Nested('nested2')]);

        $expected = [
            'array' => ['a', 'b'],
            'list_nested' => [
                ['nested_string' => 'nested1'],
                ['nested_string' => 'nested2'],
            ],
            'optional_list' => [
                ['nested_string' => 'opt1'],
                ['nested_string' => 'opt2'],
            ],
            'collection' => ['a', 'b'],
            'collection_nested' => [
                'a' => ['nested_string' => 'nested1'],
                'b' => ['nested_string' => 'nested2'],
            ],
        ];

        $data = $functionName($list);
        self::assertSame($expected, $data);
    }

    public function testArraysWithUnknownSubType(): void
    {
        $functionName = 'serialize_Tests_Liip_Serializer_Fixtures_UnknownArraySubType';
        self::generateSerializers(self::$metadataBuilder, UnknownArraySubType::class, [$functionName], [''], [], ['allow_generic_arrays' => true]);

        $list = new UnknownArraySubType();
        $unknownSubtype = ['unknown' => 'type', 'nested' => ['unknown' => 'subtype']];
        $list->unknownSubType = $unknownSubtype;

        $expected = [
            'unknown_sub_type' => $unknownSubtype,
        ];

        $data = $functionName($list);
        self::assertSame($expected, $data);
    }

    public function testMultidimensionalArraysForPrimitives(): void
    {
        $functionName = 'serialize_Tests_Liip_Serializer_Fixtures_MultidimensionalArrayForPrimitive';
        self::generateSerializers(self::$metadataBuilder, MultidimensionalArrayForPrimitive::class, [$functionName], ['']);

        $subject = new MultidimensionalArrayForPrimitive();
        $twoDims = [0 => [0], 1 => [1]];
        $fiveDims = [0 => [0 => [0 => [0 => [2]]]], 1 => [3 => [0 => [0 => [3]]]]];
        $mapOfLists = ['foo' => [0], 'bar' => 1];
        $listOfMapOfLists = [0 => ['m00' => [0 => [0], 1 => [1]], 'm01' => [0 => [0]]], 1 => ['m10' => [0 => [42]]]];

        $subject->twoDims = $twoDims;
        $subject->fiveDims = $fiveDims;
        $subject->mapOfLists = $mapOfLists;
        $subject->listOfMapOfLists = $listOfMapOfLists;

        $expected = [
            'two_dims' => $twoDims,
            'five_dims' => $fiveDims,
            'map_of_lists' => $mapOfLists,
            'list_of_map_of_lists' => $listOfMapOfLists,
        ];

        $data = $functionName($subject);
        self::assertSame($expected, $data);
    }

    public function testRecursions(): void
    {
        $functionName = 'serialize_Tests_Liip_Serializer_Fixtures_RecursionModel';
        self::generateSerializers(self::$metadataBuilder, RecursionModel::class, [$functionName], ['']);

        $model = new RecursionModel();
        $model->property = 'property';
        $model->recursion = new RecursionModel();
        $model->recursion->property = 'recursive property';
        $model->recursion->recursion = new RecursionModel();
        $model->recursion->recursion->property = 'recursive recursive property';
        $model->recursion->recursion->recursion = new RecursionModel();
        $model->recursion->recursion->recursion->property = 'recursive recursive recursive property';

        $expected = [
            'property' => 'property',
            'recursion' => [
                'property' => 'recursive property',
                'recursion' => [
                    'property' => 'recursive recursive property',
                ],
            ],
        ];

        $data = $functionName($model);
        self::assertSame($expected, $data);
    }

    public function testEmptyModel(): void
    {
        $functionName = 'serialize_Tests_Liip_Serializer_Fixtures_Model';
        self::generateSerializers(self::$metadataBuilder, Model::class, [$functionName], ['']);

        $model = new Model();
        $data = $functionName($model);

        self::assertInstanceOf(\stdClass::class, $data);
        self::assertCount(0, get_object_vars($data));
    }

    public function testEmptyModelNotUsingStdClass(): void
    {
        $functionName = 'serialize_Tests_Liip_Serializer_Fixtures_Model';
        self::generateSerializers(self::$metadataBuilder, Model::class, [$functionName]);

        $model = new Model();
        $data = $functionName($model, false);

        self::assertSame([], $data);
    }

    public function testDateTimeWithFormat(): void
    {
        $functionName = 'serialize_Tests_Liip_Serializer_Fixtures_Model';
        self::generateSerializers(self::$metadataBuilder, Model::class, [$functionName]);

        $model = new Model();
        $model->dateWithFormat = new \DateTime('2020-04-22 10:11:12');
        $data = $functionName($model);

        self::assertSame(['date_with_format' => '2020-04-22'], $data);
    }

    /**
     * An empty array must be serialized, only null must not show up.
     */
    public function testEmptyArray(): void
    {
        $functionName = 'serialize_Tests_Liip_Serializer_Fixtures_ListModel';
        self::generateSerializers(self::$metadataBuilder, ListModel::class, [$functionName]);

        $list = new ListModel();
        $list->array = [];
        $list->hashmap = [];
        $list->listNested = [];
        $data = $functionName($list);

        self::assertIsArray($data);
        self::assertArrayHasKey('array', $data);
        self::assertSame([], $data['array']);
        self::assertArrayHasKey('list_nested', $data);
        self::assertSame([], $data['list_nested']);
        self::assertArrayHasKey('hashmap', $data);
        self::assertInstanceOf(\stdClass::class, $data['hashmap']);
        self::assertCount(0, get_object_vars($data['hashmap']));
    }

    public function testEmptyArrayNotUsingStdClass(): void
    {
        $functionName = 'serialize_Tests_Liip_Serializer_Fixtures_ListModel';
        self::generateSerializers(self::$metadataBuilder, ListModel::class, [$functionName]);

        $list = new ListModel();
        $list->array = [];
        $list->hashmap = [];
        $list->listNested = [];
        $data = $functionName($list, false);

        self::assertIsArray($data);
        self::assertArrayHasKey('array', $data);
        self::assertSame([], $data['array']);
        self::assertArrayHasKey('list_nested', $data);
        self::assertSame([], $data['list_nested']);
        self::assertArrayHasKey('hashmap', $data);
        self::assertSame([], $data['hashmap']);
    }

    public function testPrivateProperty(): void
    {
        $functionName = 'serialize_Tests_Liip_Serializer_Fixtures_PrivateProperty_2';
        self::generateSerializers(self::$metadataBuilder, PrivateProperty::class, [$functionName]);

        $model = new PrivateProperty();
        $model->setApiString('apiString');
        $model->setExtra('Extra Info');

        $expected = [
            'extra' => 'Extra Info',
            'api_string' => 'apiString_setter',
        ];
        $data = $functionName($model);
        self::assertSame($expected, $data);
    }

    public function testInheritance(): void
    {
        $functionName = 'serialize_Tests_Liip_Serializer_Fixtures_Inheritance_2';
        self::generateSerializers(self::$metadataBuilder, Inheritance::class, [$functionName]);

        $model = new Inheritance();
        $model->setApiString('apiString');
        $model->setExtra('Extra Info');

        $expected = [
            'foo' => 'Extra Info',
            'api_string' => 'apiString_setter',
        ];
        $data = $functionName($model);
        self::assertSame($expected, $data);
    }

    public function testNullFieldWithGetter(): void
    {
        $functionName = 'serialize_Tests_Liip_Serializer_Fixtures_ContainsPrivateProperty_2';
        self::generateSerializers(self::$metadataBuilder, ContainsPrivateProperty::class, [$functionName]);

        $model = new ContainsPrivateProperty();
        $model->apiString = 'api string';

        $expected = [
            'api_string' => 'api string',
        ];
        $data = $functionName($model);
        self::assertSame($expected, $data);
    }

    public function testVirtualProperties(): void
    {
        $functionName = 'serialize_Tests_Liip_Serializer_Fixtures_VirtualProperties_2';
        self::generateSerializers(self::$metadataBuilder, VirtualProperties::class, [$functionName]);

        $model = new VirtualProperties();
        $model->apiString = 'apiString';

        $expected = [
            'api_string' => 'apiString',
            'api_string_virtual' => 'apiString_virtual',
        ];
        $data = $functionName($model);

        self::assertSame($expected, $data);
    }

    public function testDiscriminator(): void
    {
        $functionName = 'serialize_Tests_Liip_Serializer_Fixtures_DiscriminatorDependency_2';
        self::generateSerializers(self::$metadataBuilder, DiscriminatorDependency::class, [$functionName]);

        $model = new DiscriminatorDependency();
        $model->discriminator = new DiscriminatorFirstChild();
        $model->discriminator->firstProperty = 'my-value';

        $expected = [
            'discriminator' => [
                'first_property' => 'my-value',
                'type' => 'first',
            ],
        ];
        $data = $functionName($model);

        self::assertSame($expected, $data);
    }

    public function testComplexUnionDiscriminator(): void
    {
        if (!class_exists(UnionDiscriminator::class)) {
            self::markTestSkipped('UnionDiscriminator attribute from JMS missing');
        }

        $functionName = 'serialize_Tests_Liip_Serializer_Fixtures_ComplexUnionTyping_2';
        self::generateSerializers(self::$metadataBuilder, ComplexUnionTyping::class, [$functionName]);

        $model = new ComplexUnionTyping();
        $model->property = new DiscriminatorAuthor();
        $model->property->name = 'author-name';

        $expected = [
            'property' => [
                'name' => 'author-name',
                'objectType' => 'author',
            ],
        ];
        $data = $functionName($model);

        self::assertSame($expected, $data);

        $model = new ComplexUnionTyping();
        $model->property = new DiscriminatorComment();
        $model->property->text = 'comment-text';

        $expected = [
            'property' => [
                'text' => 'comment-text',
                'objectType' => 'comment',
            ],
        ];
        $data = $functionName($model);

        self::assertSame($expected, $data);
    }

    /**
     * @dataProvider providePrimitiveUnionDiscriminatorCases
     */
    public function testPrimitiveUnionDiscriminator(mixed $propertyValue): void
    {
        $functionName = 'serialize_Tests_Liip_Serializer_Fixtures_PrimitiveUnionTyping';
        self::generateSerializers(self::$metadataBuilder, PrimitiveUnionTyping::class, [$functionName], [''], [], ['allow_generic_arrays' => true]);

        $model = new PrimitiveUnionTyping();
        $model->property = $propertyValue;

        $expected = ['property' => $propertyValue];
        $data = $functionName($model);

        self::assertSame($expected, $data);
    }

    public static function providePrimitiveUnionDiscriminatorCases(): iterable
    {
        return [
            [42],
            ['string-value'],
            [false],
            [0.5],
            [['key' => 'value', 'another_key' => 'another_value']],
        ];
    }

    /**
     * Assert that post deserialize is never executed during serialize.
     */
    public function testPostDeserialize(): void
    {
        $functionName = 'serialize_Tests_Liip_Serializer_Fixtures_PostDeserialize_2';
        self::generateSerializers(self::$metadataBuilder, PostDeserialize::class, [$functionName]);

        $model = new PostDeserialize();
        $model->apiString = 'apiString';

        $expected = [
            'api_string' => 'apiString',
        ];
        $data = $functionName($model);

        self::assertSame($expected, $data);
        self::assertNull($model->postCalled);
    }

    public function testAccessorOrder(): void
    {
        $functionName = 'serialize_Tests_Liip_Serializer_Fixtures_AccessorOrder_2';
        self::generateSerializers(self::$metadataBuilder, AccessorOrder::class, [$functionName]);

        $model = new AccessorOrder(12, 'apiString1', 'apiString2');

        $data = $functionName($model);

        $expected = [
            'total_hits' => 12,
            'api_string2' => 'apiString2',
            'api_string1' => 'apiString1',
        ];

        self::assertSame($expected, $data);
    }

    public function testAccessorOrderInherit(): void
    {
        $functionName = 'serialize_Tests_Liip_Serializer_Fixtures_AccessorOrderInherit_2';
        self::generateSerializers(self::$metadataBuilder, AccessorOrderInherit::class, [$functionName]);

        $model = new AccessorOrderInherit(12, 'apiString1', 'apiString2', 'apiString0');

        $data = $functionName($model);

        $expected = [
            'total_hits' => 12,
            'api_string2' => 'apiString2',
            'api_string1' => 'apiString1',
            'api_string0' => 'apiString0',
        ];

        self::assertSame($expected, $data);
    }

    public function testVersioning(): void
    {
        $function = 'serialize_Tests_Liip_Serializer_Fixtures_Versions';
        $functionV1 = 'serialize_Tests_Liip_Serializer_Fixtures_Versions_1';
        $functionV2 = 'serialize_Tests_Liip_Serializer_Fixtures_Versions_2';
        $functionV3 = 'serialize_Tests_Liip_Serializer_Fixtures_Versions_3';
        $functionV4 = 'serialize_Tests_Liip_Serializer_Fixtures_Versions_4';

        self::generateSerializers(self::$metadataBuilder, Versions::class, [$function, $functionV1, $functionV2, $functionV3, $functionV4], ['', '1', '2', '3', '4']);

        $model = new Versions();
        $model->changed = 'changed';
        $model->old = 'old';
        $model->new = 'new';

        $expected = [
            'old' => 'old',
            'changed' => 'changed',
        ];
        $data = $functionV1($model);
        self::assertSame($expected, $data, 'version 1');

        $expected = [
            'old' => 'old',
            'changed' => 'changed',
        ];
        $data = $functionV2($model);
        self::assertSame($expected, $data, 'version 2');

        $expected = [
            'changed' => 'CHANGED',
            'new' => 'new',
        ];
        $data = $functionV3($model);
        self::assertSame($expected, $data, 'version 3');

        $expected = [
            'changed' => 'CHANGED',
            'new' => 'new',
        ];
        $data = $functionV4($model);
        self::assertSame($expected, $data, 'version 4');

        $expected = [
            'old' => 'old',
            'changed' => 'changed',
            'new' => 'new',
        ];
        $data = $function($model);
        self::assertSame($expected, $data, 'no version');
    }

    public function testEnum(): void
    {
        $functionName = 'serialize_Tests_Liip_Serializer_Fixtures_EnumModel';
        self::generateSerializers(self::$metadataBuilder, EnumModel::class, [$functionName], ['']);

        $model = new EnumModel();
        $model->backedString = BackedStringEnum::Hearts;
        $model->backedStringAsName = BackedStringEnum::Hearts;
        $model->backedStringWithoutAttribute = BackedStringEnum::Diamonds;
        $model->backedInt = BackedIntEnum::Low;
        $model->unit = UnitEnum::North;
        $model->backedStringArray = [BackedStringEnum::Hearts, BackedStringEnum::Diamonds];

        $data = $functionName($model);

        self::assertSame('H', $data['backed_string']);
        self::assertSame('Hearts', $data['backed_string_as_name']);
        self::assertSame('D', $data['backed_string_without_attribute']);
        self::assertSame(1, $data['backed_int']);
        self::assertSame('North', $data['unit']);
        self::assertSame(['H', 'D'], $data['backed_string_array']);
    }

    public function testHandler(): void
    {
        $functionName = 'serialize_Tests_Liip_Serializer_Fixtures_ModelWithCustomType';
        self::generateSerializers(
            self::$metadataBuilder,
            ModelWithCustomType::class,
            [$functionName],
            [],
            [],
            ['handlers' => [new CustomTypeHandler()]]
        );

        $model = new ModelWithCustomType();
        $model->value = new CustomType('hello');
        $model->values = [new CustomType('foo'), new CustomType('bar')];

        $data = $functionName($model);

        self::assertSame('hello', $data['value']);
        self::assertSame(['foo', 'bar'], $data['values']);
    }

    public function testInaccessibleProperty(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('is not public and no getter has been defined');

        self::generateSerializers(self::$metadataBuilder, InaccessiblePrivateProperty::class, ['should never get here']);
    }
}
