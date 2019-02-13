<?php

declare(strict_types=1);

namespace Tests\Liip\Serializer\Unit;

use Doctrine\Common\Annotations\AnnotationReader;
use Liip\MetadataParser\Builder;
use Liip\MetadataParser\ModelParser\JMSParser;
use Liip\Serializer\DeserializerGenerator;
use Liip\Serializer\Template\Deserialization;
use Tests\Liip\Serializer\Fixtures\ContainsNonEmptyConstructor;
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
            'date_immutable' => '2016-06-01T00:00:00+02:00',
        ];

        /** @var Model $model */
        $model = $functionName($input);
        $this->assertInstanceOf(Model::class, $model);
        $this->assertSame('api', $model->apiString);
        $this->assertSame('details', $model->detailString);
        $this->assertNull($model->unAnnotated);
        $this->assertInstanceOf(Nested::class, $model->nestedField);
        $this->assertSame('nested', $model->nestedField->nestedString);
        $this->assertInstanceOf(\DateTime::class, $model->date);
        $this->assertSame('2018-08-03', $model->date->format('Y-m-d'));
        $this->assertInstanceOf(\DateTimeImmutable::class, $model->dateImmutable);
        $this->assertSame('2016-06-01', $model->dateImmutable->format('Y-m-d'));
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
        $this->assertInstanceOf(ListModel::class, $model);
        $this->assertSame(['a', 'b'], $model->array);
        $this->assertInternalType('array', $model->listNested);
        $this->assertCount(2, $model->listNested);
        foreach ($model->listNested as $index => $nested) {
            $this->assertInstanceOf(Nested::class, $nested);
            $this->assertSame('nested'.($index + 1), $nested->nestedString);
        }
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
        $this->assertInstanceOf(PrivateProperty::class, $model);
        $this->assertSame('apiString_setter', $model->getApiString());
        $this->assertSame('More Info', $model->getExtra());
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
        $this->assertInstanceOf(Inheritance::class, $model);
        $this->assertSame('apiString_setter', $model->getApiString());
        $this->assertSame('More Info', $model->getExtra());
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
        $this->assertInstanceOf(ContainsNonEmptyConstructor::class, $model);
        $child = $model->child;
        $this->assertInstanceOf(NonEmptyConstructor::class, $child);
        $this->assertSame('apiString', $child->getApiString());
        $this->assertSame('optional', $child->getOptional());
        $this->assertSame(['foo', 'bar'], $child->getOnlyArgument());

        $input = [
            'child' => [
                'api_string' => 'apiString',
                'optional' => 'custom',
            ],
        ];
        /** @var ContainsNonEmptyConstructor $model */
        $model = $functionName($input);
        $this->assertInstanceOf(ContainsNonEmptyConstructor::class, $model);
        $child = $model->child;
        $this->assertInstanceOf(NonEmptyConstructor::class, $child);
        $this->assertSame('apiString', $child->getApiString());
        $this->assertSame('custom', $child->getOptional());
        $this->assertSame(['foo', 'bar'], $child->getOnlyArgument());
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
        $this->assertInstanceOf(VirtualProperties::class, $model);
        $this->assertSame('apiString', $model->apiString);
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
        $this->assertInstanceOf(PostDeserialize::class, $model);
        $this->assertSame('apiString', $model->apiString);
        $this->assertSame('post has been called', $model->postCalled);
    }
}
