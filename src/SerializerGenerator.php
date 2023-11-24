<?php

declare(strict_types=1);

namespace Liip\Serializer;

use Liip\MetadataParser\Builder;
use Liip\MetadataParser\Metadata\ClassMetadata;
use Liip\MetadataParser\Metadata\PropertyMetadata;
use Liip\MetadataParser\Metadata\PropertyType;
use Liip\MetadataParser\Metadata\PropertyTypeArray;
use Liip\MetadataParser\Metadata\PropertyTypeClass;
use Liip\MetadataParser\Metadata\PropertyTypeDateTime;
use Liip\MetadataParser\Metadata\PropertyTypePrimitive;
use Liip\MetadataParser\Metadata\PropertyTypeUnknown;
use Liip\MetadataParser\Reducer\GroupReducer;
use Liip\MetadataParser\Reducer\PreferredReducer;
use Liip\MetadataParser\Reducer\TakeBestReducer;
use Liip\MetadataParser\Reducer\VersionReducer;
use Liip\Serializer\Configuration\GeneratorConfiguration;
use Liip\Serializer\Template\Serialization;
use Symfony\Component\Filesystem\Filesystem;

final class SerializerGenerator
{
    private const FILENAME_PREFIX = 'serialize';

    private Filesystem $filesystem;

    public function __construct(
        private Serialization $templating,
        private GeneratorConfiguration $configuration,
        private string $cacheDirectory
    ) {
        $this->filesystem = new Filesystem();
    }

    /**
     * @param list<string> $serializerGroups
     */
    public static function buildSerializerFunctionName(string $className, ?string $apiVersion, array $serializerGroups): string
    {
        $functionName = self::FILENAME_PREFIX.'_'.$className;
        if (\count($serializerGroups)) {
            $functionName .= '_'.implode('_', $serializerGroups);
        }
        if (null !== $apiVersion) {
            $functionName .= '_'.$apiVersion;
        }

        return preg_replace('/[^a-zA-Z0-9_]/', '_', $functionName);
    }

    public function generate(Builder $metadataBuilder): void
    {
        $this->filesystem->mkdir($this->cacheDirectory);

        foreach ($this->configuration as $classToGenerate) {
            foreach ($classToGenerate as $groupCombination) {
                $className = $classToGenerate->getClassName();
                foreach ($groupCombination->getVersions() as $version) {
                    $groups = $groupCombination->getGroups();
                    if ('' === $version) {
                        if ([] === $groups) {
                            $metadata = $metadataBuilder->build($className, [
                                new PreferredReducer(),
                                new TakeBestReducer(),
                            ]);
                            $this->writeFile($className, null, [], $metadata);
                        } else {
                            $metadata = $metadataBuilder->build($className, [
                                new GroupReducer($groups),
                                new PreferredReducer(),
                                new TakeBestReducer(),
                            ]);
                            $this->writeFile($className, null, $groups, $metadata);
                        }
                    } else {
                        $metadata = $metadataBuilder->build($className, [
                            new VersionReducer($version),
                            new GroupReducer($groups),
                            new TakeBestReducer(),
                        ]);
                        $this->writeFile($className, $version, $groups, $metadata);
                    }
                }
            }
        }
    }

    /**
     * @param list<string> $serializerGroups
     */
    private function writeFile(
        string $className,
        ?string $apiVersion,
        array $serializerGroups,
        ClassMetadata $classMetadata
    ): void {
        $functionName = self::buildSerializerFunctionName($className, $apiVersion, $serializerGroups);

        $code = $this->templating->renderFunction(
            $functionName,
            $className,
            $this->generateCodeForClass($classMetadata, $apiVersion, $serializerGroups, '', '$model')
        );

        $this->filesystem->dumpFile(sprintf('%s/%s.php', $this->cacheDirectory, $functionName), $code);
    }

    /**
     * @param list<string>                $serializerGroups
     * @param array<string, positive-int> $stack
     */
    private function generateCodeForClass(
        ClassMetadata $classMetadata,
        ?string $apiVersion,
        array $serializerGroups,
        string $arrayPath,
        string $modelPath,
        array $stack = []
    ): string {
        $stack[$classMetadata->getClassName()] = ($stack[$classMetadata->getClassName()] ?? 0) + 1;

        $code = '';
        foreach ($classMetadata->getProperties() as $propertyMetadata) {
            $code .= $this->generateCodeForField($propertyMetadata, $apiVersion, $serializerGroups, $arrayPath, $modelPath, $stack);
        }

        return $this->templating->renderClass($arrayPath, $code);
    }

    /**
     * @param list<string>                $serializerGroups
     * @param array<string, positive-int> $stack
     */
    private function generateCodeForField(
        PropertyMetadata $propertyMetadata,
        ?string $apiVersion,
        array $serializerGroups,
        string $arrayPath,
        string $modelPath,
        array $stack
    ): string {
        if (Recursion::hasMaxDepthReached($propertyMetadata, $stack)) {
            return '';
        }

        $modelPropertyPath = $modelPath.'->'.$propertyMetadata->getName();
        $fieldPath = $arrayPath.'["'.$propertyMetadata->getSerializedName().'"]';

        if ($propertyMetadata->getAccessor()->hasGetterMethod()) {
            $tempVariable = str_replace(['->', '[', ']', '$'], '', $modelPath).ucfirst($propertyMetadata->getName());

            return $this->templating->renderConditional(
                $this->templating->renderTempVariable($tempVariable, $this->templating->renderGetter($modelPath, $propertyMetadata->getAccessor()->getGetterMethod())),
                $this->generateCodeForFieldType($propertyMetadata->getType(), $apiVersion, $serializerGroups, $fieldPath, '$'.$tempVariable, $stack)
            );
        }
        if (!$propertyMetadata->isPublic()) {
            throw new \Exception(sprintf('Property %s is not public and no getter has been defined. Stack %s', $modelPropertyPath, var_export($stack, true)));
        }

        return $this->templating->renderConditional(
            $modelPropertyPath,
            $this->generateCodeForFieldType($propertyMetadata->getType(), $apiVersion, $serializerGroups, $fieldPath, $modelPropertyPath, $stack)
        );
    }

    /**
     * @param list<string>                $serializerGroups
     * @param array<string, positive-int> $stack
     */
    private function generateCodeForFieldType(
        PropertyType $type,
        ?string $apiVersion,
        array $serializerGroups,
        string $fieldPath,
        string $modelPropertyPath,
        array $stack
    ): string {
        switch ($type) {
            case $type instanceof PropertyTypeDateTime:
                if (null !== $type->getZone()) {
                    throw new \RuntimeException('Timezone support is not implemented');
                }
                $dateFormat = $type->getFormat() ?: \DateTime::ISO8601;

                return $this->templating->renderAssign(
                    $fieldPath,
                    $this->templating->renderDateTime($modelPropertyPath, $dateFormat)
                );

            case $type instanceof PropertyTypePrimitive:
            case $type instanceof PropertyTypeUnknown:
                // for arrays of scalars, copy the field even when its an empty array
                return $this->templating->renderAssign($fieldPath, $modelPropertyPath);

            case $type instanceof PropertyTypeClass:
                return $this->generateCodeForClass($type->getClassMetadata(), $apiVersion, $serializerGroups, $fieldPath, $modelPropertyPath, $stack);

            case $type instanceof PropertyTypeArray:
                return $this->generateCodeForArray($type, $apiVersion, $serializerGroups, $fieldPath, $modelPropertyPath, $stack);

            default:
                throw new \Exception('Unexpected type '.$type::class.' at '.$modelPropertyPath);
        }
    }

    /**
     * @param list<string>                $serializerGroups
     * @param array<string, positive-int> $stack
     */
    private function generateCodeForArray(
        PropertyTypeArray $type,
        ?string $apiVersion,
        array $serializerGroups,
        string $arrayPath,
        string $modelPath,
        array $stack
    ): string {
        $index = '$index'.mb_strlen($arrayPath);
        $subType = $type->getSubType();

        switch ($subType) {
            case $subType instanceof PropertyTypePrimitive:
            case $subType instanceof PropertyTypeArray && self::isArrayForPrimitive($subType):
            case $subType instanceof PropertyTypeUnknown && $this->configuration->shouldAllowGenericArrays():
                return $this->templating->renderArrayAssign($arrayPath, $modelPath);

            case $subType instanceof PropertyTypeArray:
                $innerCode = $this->generateCodeForArray($subType, $apiVersion, $serializerGroups, $arrayPath.'['.$index.']', $modelPath.'['.$index.']', $stack);
                break;

            case $subType instanceof PropertyTypeClass:
                $innerCode = $this->generateCodeForClass($subType->getClassMetadata(), $apiVersion, $serializerGroups, $arrayPath.'['.$index.']', $modelPath.'['.$index.']', $stack);
                break;

            default:
                throw new \Exception('Unexpected array subtype '.$subType::class);
        }

        if ('' === $innerCode) {
            if ($type->isHashmap()) {
                return $this->templating->renderLoopHashmapEmpty($arrayPath);
            }

            return $this->templating->renderLoopArrayEmpty($arrayPath);
        }

        if ($type->isHashmap()) {
            return $this->templating->renderLoopHashmap($arrayPath, $modelPath, $index, $innerCode);
        }

        return $this->templating->renderLoopArray($arrayPath, $modelPath, $index, $innerCode);
    }

    private static function isArrayForPrimitive(PropertyTypeArray $type): bool
    {
        return match ($type->getSubType()::class) {
            PropertyTypePrimitive::class => true,
            PropertyTypeArray::class => self::isArrayForPrimitive($type->getSubType()),
            default => false,
        };
    }
}
