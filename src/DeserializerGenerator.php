<?php

declare(strict_types=1);

namespace Liip\Serializer;

use Liip\MetadataParser\Builder;
use Liip\MetadataParser\Metadata\ClassMetadata;
use Liip\MetadataParser\Metadata\PropertyMetadata;
use Liip\MetadataParser\Metadata\PropertyTypeArray;
use Liip\MetadataParser\Metadata\PropertyTypeClass;
use Liip\MetadataParser\Metadata\PropertyTypeDateTime;
use Liip\MetadataParser\Metadata\PropertyTypePrimitive;
use Liip\MetadataParser\Metadata\PropertyTypeUnknown;
use Liip\MetadataParser\Reducer\TakeBestReducer;
use Liip\Serializer\Configuration\ClassToGenerate;
use Liip\Serializer\Configuration\GeneratorConfiguration;
use Liip\Serializer\Path\ArrayPath;
use Liip\Serializer\Path\ModelPath;
use Liip\Serializer\Template\Deserialization;
use Symfony\Component\Filesystem\Filesystem;

final class DeserializerGenerator
{
    private const FILENAME_PREFIX = 'deserialize';

    private Filesystem $filesystem;

    private GeneratorConfiguration $configuration;

    /**
     * @param list<class-string> $classesToGenerate This is a list of FQCN classnames
     */
    public function __construct(
        private Deserialization $templating,
        array $classesToGenerate,
        private string $cacheDirectory,
        ?GeneratorConfiguration $configuration = null
    ) {
        $this->filesystem = new Filesystem();
        $this->configuration = $this->createGeneratorConfiguration($configuration, $classesToGenerate);
    }

    public static function buildDeserializerFunctionName(string $className): string
    {
        return self::FILENAME_PREFIX.'_'.str_replace('\\', '_', $className);
    }

    public function generate(Builder $metadataBuilder): void
    {
        $this->filesystem->mkdir($this->cacheDirectory);

        /** @var ClassToGenerate $classToGenerate */
        foreach ($this->configuration as $classToGenerate) {
            // we do not use the oldest version reducer here and hope for the best
            // otherwise we end up with generated property names for accessor methods
            $classMetadata = $metadataBuilder->build($classToGenerate->getClassName(), [
                new TakeBestReducer(),
            ]);
            $this->writeFile($classMetadata);
        }
    }

    private function writeFile(ClassMetadata $classMetadata): void
    {
        if (\count($classMetadata->getConstructorParameters())) {
            throw new \Exception(sprintf('We currently do not support deserializing when the root class has a non-empty constructor. Class %s', $classMetadata->getClassName()));
        }

        $functionName = self::buildDeserializerFunctionName($classMetadata->getClassName());
        $arrayPath = new ArrayPath('jsonData');

        $code = $this->templating->renderFunction(
            $functionName,
            $classMetadata->getClassName(),
            (string) $arrayPath,
            $this->generateCodeForClass($classMetadata, $arrayPath, new ModelPath('model'))
        );

        $this->filesystem->dumpFile(sprintf('%s/%s.php', $this->cacheDirectory, $functionName), $code);
    }

    /**
     * @param array<string, positive-int> $stack
     */
    private function generateCodeForClass(
        ClassMetadata $classMetadata,
        ArrayPath $arrayPath,
        ModelPath $modelPath,
        array $stack = []
    ): string {
        $stack[$classMetadata->getClassName()] = ($stack[$classMetadata->getClassName()] ?? 0) + 1;

        $constructorArgumentNames = [];
        $overwrittenNames = [];
        $initCode = '';
        $code = '';
        foreach ($classMetadata->getProperties() as $propertyMetadata) {
            $propertyArrayPath = $arrayPath->withFieldName($propertyMetadata->getSerializedName());

            if ($classMetadata->hasConstructorParameter($propertyMetadata->getName())) {
                $argument = $classMetadata->getConstructorParameter($propertyMetadata->getName());
                $default = var_export($argument->isRequired() ? null : $argument->getDefaultValue(), true);
                $tempVariable = ModelPath::tempVariable([(string) $modelPath, $propertyMetadata->getName()]);
                if (\array_key_exists($propertyMetadata->getName(), $constructorArgumentNames)) {
                    $overwrittenNames[$propertyMetadata->getName()] = true;
                }
                $constructorArgumentNames[$propertyMetadata->getName()] = (string) $tempVariable;

                $initCode .= $this->templating->renderArgument(
                    (string) $tempVariable,
                    $default,
                    $this->generateCodeForField($propertyMetadata, $propertyArrayPath, $tempVariable, $stack)
                );
            } else {
                $code .= $this->generateCodeForProperty($propertyMetadata, $propertyArrayPath, $modelPath, $stack);
            }
        }

        foreach ($classMetadata->getPostDeserializeMethods() as $method) {
            $code .= $this->templating->renderPostMethod((string) $modelPath, $method);
        }

        $constructorArguments = [];
        foreach ($classMetadata->getConstructorParameters() as $definition) {
            if (\array_key_exists($definition->getName(), $constructorArgumentNames)) {
                $constructorArguments[] = $constructorArgumentNames[$definition->getName()];
                continue;
            }
            if ($definition->isRequired()) {
                $msg = sprintf('Unknown constructor argument "%s". Class %s only has properties that tell how to handle %s.', $definition->getName(), $classMetadata->getClassName(), implode(', ', array_keys($constructorArgumentNames)));
                if ($overwrittenNames) {
                    $msg .= sprintf(' Multiple definitions for fields %s seen - the last one overwrites previous ones.', implode(', ', array_keys($overwrittenNames)));
                }
                throw new \Exception($msg);
            }
            $constructorArguments[] = var_export($definition->getDefaultValue(), true);
        }
        if (\count($constructorArgumentNames) > 0) {
            $code .= $this->templating->renderUnset(array_values($constructorArgumentNames));
        }

        return $this->templating->renderClass((string) $modelPath, $classMetadata->getClassName(), $constructorArguments, $code, $initCode);
    }

    /**
     * @param array<string, positive-int> $stack
     */
    private function generateCodeForProperty(
        PropertyMetadata $propertyMetadata,
        ArrayPath $arrayPath,
        ModelPath $modelPath,
        array $stack
    ): string {
        if ($propertyMetadata->isReadOnly()) {
            return '';
        }

        if (Recursion::hasMaxDepthReached($propertyMetadata, $stack)) {
            return '';
        }

        if ($propertyMetadata->getAccessor()->hasSetterMethod()) {
            $tempVariable = ModelPath::tempVariable([(string) $modelPath, $propertyMetadata->getName()]);
            $code = $this->generateCodeForField($propertyMetadata, $arrayPath, $tempVariable, $stack);
            $code .= $this->templating->renderConditional(
                (string) $tempVariable,
                $this->templating->renderSetter((string) $modelPath, $propertyMetadata->getAccessor()->getSetterMethod(), (string) $tempVariable)
            );
            $code .= $this->templating->renderUnset([(string) $tempVariable]);

            return $code;
        }

        $modelPropertyPath = $modelPath->withPath($propertyMetadata->getName());

        return $this->generateCodeForField($propertyMetadata, $arrayPath, $modelPropertyPath, $stack);
    }

    /**
     * @param array<string, positive-int> $stack
     */
    private function generateCodeForField(
        PropertyMetadata $propertyMetadata,
        ArrayPath $arrayPath,
        ModelPath $modelPath,
        array $stack
    ): string {
        return $this->templating->renderConditional(
            (string) $arrayPath,
            $this->generateInnerCodeForFieldType($propertyMetadata, $arrayPath, $modelPath, $stack)
        );
    }

    /**
     * @param array<string, positive-int> $stack
     */
    private function generateInnerCodeForFieldType(
        PropertyMetadata $propertyMetadata,
        ArrayPath $arrayPath,
        ModelPath $modelPropertyPath,
        array $stack
    ): string {
        $type = $propertyMetadata->getType();

        switch ($type) {
            case $type instanceof PropertyTypeArray:
                if ($type->isTraversable()) {
                    return $this->generateCodeForArrayCollection($propertyMetadata, $type, $arrayPath, $modelPropertyPath, $stack);
                }

                return $this->generateCodeForArray($type, $arrayPath, $modelPropertyPath, $stack);

            case $type instanceof PropertyTypeDateTime:
                $formats = $type->getDeserializeFormats() ?: (\is_string($type->getFormat()) ? [$type->getFormat()] : $type->getFormat());
                if (null !== $formats) {
                    return $this->templating->renderAssignDateTimeFromFormat($type->isImmutable(), (string) $modelPropertyPath, (string) $arrayPath, $formats, $type->getZone());
                }

                return $this->templating->renderAssignDateTimeToField($type->isImmutable(), (string) $modelPropertyPath, (string) $arrayPath);

            case $type instanceof PropertyTypePrimitive && 'float' === $type->getTypeName():
                return $this->templating->renderAssignJsonDataToFieldWithCasting((string) $modelPropertyPath, (string) $arrayPath, 'float');

            case $type instanceof PropertyTypePrimitive:
            case $type instanceof PropertyTypeUnknown:
                return $this->templating->renderAssignJsonDataToField((string) $modelPropertyPath, (string) $arrayPath);

            case $type instanceof PropertyTypeClass:
                return $this->generateCodeForClass($type->getClassMetadata(), $arrayPath, $modelPropertyPath, $stack);

            default:
                throw new \Exception('Unexpected type '.$type::class.' at '.$modelPropertyPath);
        }
    }

    /**
     * @param array<string, positive-int> $stack
     */
    private function generateCodeForArray(
        PropertyTypeArray $type,
        ArrayPath $arrayPath,
        ModelPath $modelPath,
        array $stack
    ): string {
        if ($type->getSubType() instanceof PropertyTypePrimitive) {
            // for arrays of scalars, copy the field even when its an empty array
            return $this->templating->renderAssignJsonDataToField((string) $modelPath, (string) $arrayPath);
        }

        $index = ModelPath::indexVariable((string) $arrayPath);
        $arrayPropertyPath = $arrayPath->withVariable((string) $index);
        $modelPropertyPath = $modelPath->withArray((string) $index);
        $subType = $type->getSubType();

        switch ($subType) {
            case $subType instanceof PropertyTypeArray:
                $innerCode = $this->generateCodeForArray($subType, $arrayPropertyPath, $modelPropertyPath, $stack);
                break;

            case $subType instanceof PropertyTypeClass:
                $innerCode = $this->generateCodeForClass($subType->getClassMetadata(), $arrayPropertyPath, $modelPropertyPath, $stack);
                break;

            case $subType instanceof PropertyTypeUnknown && $this->configuration->shouldAllowGenericArrays():
                return $this->templating->renderAssignJsonDataToField((string) $modelPath, (string) $arrayPath);

            default:
                throw new \Exception('Unexpected array subtype '.$subType::class);
        }

        if ('' === $innerCode) {
            return '';
        }

        $code = $this->templating->renderInitArray((string) $modelPath);
        $code .= $this->templating->renderLoop((string) $arrayPath, (string) $index, $innerCode);

        return $code;
    }

    /**
     * @param array<string, positive-int> $stack
     */
    private function generateCodeForArrayCollection(
        PropertyMetadata $propertyMetadata,
        PropertyTypeArray $type,
        ArrayPath $arrayPath,
        ModelPath $modelPath,
        array $stack
    ): string {
        $tmpVariable = ModelPath::tempVariable([(string) $modelPath, $propertyMetadata->getName()]);
        $innerCode = $this->generateCodeForArray($type, $arrayPath, $tmpVariable, $stack);

        if ('' === $innerCode) {
            return '';
        }

        return $innerCode.$this->templating->renderArrayCollection((string) $modelPath, (string) $tmpVariable);
    }

    /**
     * @param list<class-string> $classesToGenerate
     */
    private function createGeneratorConfiguration(
        ?GeneratorConfiguration $configuration,
        array $classesToGenerate
    ): GeneratorConfiguration {
        if (null === $configuration) {
            $configuration = new GeneratorConfiguration([], []);
        }

        foreach ($classesToGenerate as $className) {
            $configuration->addClassToGenerate(new ClassToGenerate($configuration, $className));
        }

        return $configuration;
    }
}
