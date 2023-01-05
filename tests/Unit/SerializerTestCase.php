<?php

declare(strict_types=1);

namespace Tests\Liip\Serializer\Unit;

use Liip\MetadataParser\Builder;
use Liip\MetadataParser\ModelParser\ModelParserInterface;
use Liip\MetadataParser\Parser;
use Liip\MetadataParser\RecursionChecker;
use Liip\Serializer\Configuration\GeneratorConfiguration;
use Liip\Serializer\DeserializerGenerator;
use Liip\Serializer\SerializerGenerator;
use Liip\Serializer\Template\Deserialization;
use Liip\Serializer\Template\Serialization;
use PHPUnit\Framework\TestCase;

class SerializerTestCase extends TestCase
{
    /**
     * @param ModelParserInterface[] $parsers
     */
    protected static function createMetadataBuilder(array $parsers, $expectedRecursions = []): Builder
    {
        return new Builder(new Parser($parsers), new RecursionChecker(null, $expectedRecursions));
    }

    /**
     * Generate the deserializer for the specified class and make sure the file and function exist.
     */
    protected static function generateDeserializer(Builder $metadataBuilder, string $classToGenerate, string $functionName): void
    {
        $templating = new Deserialization();
        $deserializerGenerator = new DeserializerGenerator($templating, [$classToGenerate], '/tmp');

        $deserializerGenerator->generate($metadataBuilder);

        $filePath = '/tmp/'.$functionName.'.php';
        static::assertFileExists($filePath);
        require_once $filePath;
        static::assertTrue(\function_exists($functionName));
    }

    protected static function generateSerializers(Builder $metadataBuilder, string $classToGenerate, array $functionNames, array $versions = ['2'], array $groups = [], bool $assignUnknownArrays = false): void
    {
        $templating = new Serialization();
        $configuration = GeneratorConfiguration::createFomArray([
            'default_group_combinations' => $groups,
            'default_versions' => $versions,
            'classes' => [
                $classToGenerate => [],
            ],
            'assign_unknown_arrays' => $assignUnknownArrays,
        ]);
        $serializerGenerator = new SerializerGenerator($templating, $configuration, '/tmp');

        $serializerGenerator->generate($metadataBuilder);

        foreach ($functionNames as $functionName) {
            $filePath = '/tmp/'.$functionName.'.php';
            static::assertFileExists($filePath);
            require_once $filePath;
            static::assertTrue(\function_exists($functionName));
        }
    }
}
