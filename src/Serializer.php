<?php

declare(strict_types=1);

namespace Liip\Serializer;

use Liip\Serializer\Exception\Exception;
use Liip\Serializer\Exception\UnsupportedFormatException;
use Liip\Serializer\Exception\UnsupportedTypeException;
use Pnz\JsonException\Json;

/**
 * A serializer that loads the pre-generated PHP function for the requested version and groups.
 *
 * Almost all decisions have been taken during generating the PHP code, rather than at runtime.
 *
 * The code generation is - at least for now - only implemented for JSON.
 */
final class Serializer implements SerializerInterface
{
    /**
     * @var string
     */
    private $cacheDirectory;

    public function __construct(string $cacheDirectory)
    {
        $this->cacheDirectory = $cacheDirectory;
    }

    /**
     * {@inheritdoc}
     *
     * Serializing primitive types is not currently implemented and will lead
     * to an UnsupportedTypeException.
     */
    public function serialize($data, string $format, ?Context $context = null): string
    {
        if ('json' !== $format) {
            throw new UnsupportedFormatException('Liip serializer only supports JSON for now');
        }

        try {
            return Json::encode($this->objectToArray($data, true, $context), \JSON_UNESCAPED_SLASHES);
        } catch (\JsonException $e) {
            throw new Exception(sprintf('Failed to JSON encode data for %s. This is not supposed to happen.', \is_object($data) ? \get_class($data) : \gettype($data)), 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     *
     * Version or groups are currently not implemented for deserialization and
     * passing a context with one of those values set will lead to an Exception.
     */
    public function deserialize(string $data, string $type, string $format, ?Context $context = null)
    {
        if ('json' !== $format) {
            throw new UnsupportedFormatException('Liip serializer only supports JSON for now');
        }

        try {
            $array = Json::decode($data, true);
        } catch (\JsonException $e) {
            throw new Exception('Failed to JSON decode data. This is not supposed to happen.', 0, $e);
        }

        return $this->arrayToObject($array, $type, $context);
    }

    /**
     * {@inheritdoc}
     *
     * Serializing primitive types is not currently implemented and will lead
     * to an UnsupportedTypeException.
     */
    public function toArray($data, ?Context $context = null): array
    {
        return $this->objectToArray($data, false, $context);
    }

    /**
     * {@inheritdoc}
     *
     * Version or groups are currently not implemented for deserialization and
     * passing a context with one of those values set will lead to an Exception.
     */
    public function fromArray(array $data, string $type, ?Context $context = null)
    {
        return $this->arrayToObject($data, $type, $context);
    }

    private function arrayToObject(array $data, string $type, ?Context $context)
    {
        if ($context && ($context->getVersion() || \count($context->getGroups()))) {
            throw new Exception('Version and group support is not implemented for deserialization. It is only supported for serialization');
        }

        $functionName = DeserializerGenerator::buildDeserializerFunctionName($type);
        $filename = sprintf('%s/%s.php', $this->cacheDirectory, $functionName);
        if (!file_exists($filename)) {
            throw UnsupportedTypeException::typeUnsupportedDeserialization($type);
        }
        require_once $filename;

        if (!\is_callable($functionName)) {
            throw new Exception(sprintf('Internal Error: Deserializer for %s in file %s does not have expected function %s', $type, $filename, $functionName));
        }

        try {
            return $functionName($data);
        } catch (\Throwable $t) {
            throw new Exception('Error during deserialization', 0, $t);
        }
    }

    private function objectToArray($data, bool $useStdClass, ?Context $context): array
    {
        if (!\is_object($data)) {
            throw new UnsupportedTypeException('The Liip Serializer only works for objects');
        }
        $type = \get_class($data);
        $groups = [];
        $version = null;
        if ($context) {
            $groups = $context->getGroups();
            if ($context->getVersion()) {
                $version = $context->getVersion();
            }
        }
        $functionName = SerializerGenerator::buildSerializerFunctionName($type, $version ? (string) $version : null, $groups);
        $filename = sprintf('%s/%s.php', $this->cacheDirectory, $functionName);
        if (!\file_exists($filename)) {
            throw UnsupportedTypeException::typeUnsupportedSerialization($type, $version, $groups);
        }

        require_once $filename;

        if (!\is_callable($functionName)) {
            throw new Exception(sprintf('Internal Error: Serializer for %s in file %s does not have expected function %s', $type, $filename, $functionName));
        }

        try {
            return $functionName($data, $useStdClass);
        } catch (\Throwable $t) {
            throw new Exception('Error during serialization', 0, $t);
        }
    }
}
