<?php

declare(strict_types=1);

namespace Liip\Serializer;

use JMS\Serializer\ArrayTransformerInterface;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializationContext as JMSSerializationContext;
use JMS\Serializer\SerializerInterface;
use Pnz\JsonException\Json;
use Psr\Log\LoggerInterface;

/**
 * A serializer that loads the pre-generated PHP function for the requested version and groups.
 *
 * This is way faster than going through the real JMS serializer. Most decisions are taken during
 * generating the PHP code, rather than at runtime.
 *
 * It is (at least for now) only implemented for JSON, and only used for selected models. If no
 * function is found for the requested version / groups, or if any error occurs, the transformer
 * falls back to the default JMS serializer.
 */
class GeneratedSerializer implements SerializerInterface, ArrayTransformerInterface
{
    /**
     * The original serializer *MUST* implement both SerializerInterface and ArrayTransformerInterface interfaces
     *
     * @var SerializerInterface&ArrayTransformerInterface Fallback for when the type or format is not supported
     */
    private $originalSerializer;

    /**
     * @var string
     */
    private $cacheDirectory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var bool[]|null A hashmap with FQN classnames that should be handled with the generated serializer or null to always try to find a generated serializer
     */
    private $enabledClasses;

    /**
     * @param object        $originalSerializer must implement both SerializerInterface and ArrayTransformerInterface interfaces
     * @param string[]|null $enabledClasses     list of fully qualified class names for which to use the generated serializer.
     *                                          Null indicates that we attempt to use this serializer with all classes
     */
    public function __construct($originalSerializer, string $cacheDirectory, LoggerInterface $logger, ?array $enabledClasses = null)
    {
        if (!$originalSerializer instanceof SerializerInterface
            || !$originalSerializer instanceof ArrayTransformerInterface
        ) {
            throw new \InvalidArgumentException(sprintf(
                'Original serializer must implement both ArrayTransformerInterface and SerializerInterface, but is %s',
                \get_class($originalSerializer)
            ));
        }
        $this->originalSerializer = $originalSerializer;
        $this->cacheDirectory = $cacheDirectory;
        $this->logger = $logger;
        if (null === $enabledClasses) {
            $this->enabledClasses = null;
        } else {
            $map = array_combine($enabledClasses, array_fill(0, \count($enabledClasses), true));
            \assert(\is_array($map));
            $this->enabledClasses = $map;
        }
    }

    public function serialize($data, $format, JMSSerializationContext $context = null)
    {
        if ('json' !== $format || !$this->useGeneratedSerializer($data, $context)) {
            return $this->originalSerializer->serialize($data, $format, $context);
        }

        return Json::encode($this->objectToArray($data, $context, true), \JSON_UNESCAPED_SLASHES);
    }

    public function deserialize($data, $type, $format, DeserializationContext $context = null)
    {
        if ('json' !== $format || !$this->useGeneratedDeserializer($type, $context)) {
            return $this->originalSerializer->deserialize($data, $type, $format, $context);
        }

        return $this->arrayToObject(Json::decode($data, true), $type, $context);
    }

    public function toArray($data, JMSSerializationContext $context = null)
    {
        if (!$this->useGeneratedSerializer($data, $context)) {
            return $this->originalSerializer->toArray($data, $context);
        }

        return $this->objectToArray($data, $context, false);
    }

    public function fromArray(array $data, $type, DeserializationContext $context = null)
    {
        if (!$this->useGeneratedDeserializer($type, $context)) {
            return $this->originalSerializer->fromArray($data, $type, $context);
        }

        return $this->arrayToObject($data, $type, $context);
    }

    private function arrayToObject(array $data, string $type, ?DeserializationContext $context)
    {
        $functionName = DeserializerGenerator::buildDeserializerFunctionName($type);
        $filename = sprintf('%s/%s.php', $this->cacheDirectory, $functionName);
        if (file_exists($filename)) {
            require_once $filename;

            if (\is_callable($functionName)) {
                try {
                    return $functionName($data);
                } catch (\Throwable $t) {
                    $this->logger->warning('Failed to convert an array to an object with the generated serializer, message {exceptionMessage}. Falling back to jms fromArray.', [
                        'exceptionMessage' => $t->getMessage(),
                        'exception' => $t,
                    ]);

                    return $this->originalSerializer->fromArray($data, $type, $context);
                }
            }
        }

        $this->logger->info('Did not find deserializer function {functionName}. Falling back to jms fromArray.', [
            'functionName' => $functionName,
        ]);

        return $this->originalSerializer->fromArray($data, $type, $context);
    }

    private function objectToArray($data, ?JMSSerializationContext $context, bool $useStdClass): array
    {
        $groups = [];
        $version = null;
        if ($context) {
            if ($context->hasAttribute('groups')) {
                $groups = $context->getAttribute('groups');
            }
            if ($context->hasAttribute('version')) {
                $version = $context->getAttribute('version');
            }
        }
        $functionName = SerializerGenerator::buildSerializerFunctionName(\get_class($data), $version ? (string) $version : null, $groups);
        $filename = sprintf('%s/%s.php', $this->cacheDirectory, $functionName);
        if (file_exists($filename)) {
            require_once $filename;

            if (\is_callable($functionName)) {
                try {
                    return $functionName($data, $useStdClass);
                } catch (\Throwable $t) {
                    $this->logger->warning('Failed to convert an object to an array with the generated serializer, message {exceptionMessage}. Falling back to jms toArray.', [
                        'exceptionMessage' => $t->getMessage(),
                        'exception' => $t,
                    ]);

                    return $this->originalSerializer->toArray($data, $context);
                }
            }
        }

        $this->logger->info('Did not find serializer function {functionName}. Falling back to jms toArray.', [
            'functionName' => $functionName,
        ]);

        return $this->originalSerializer->toArray($data, $context);
    }

    private function useGeneratedSerializer($data, ?JMSSerializationContext $context): bool
    {
        if (!\is_object($data)) {
            // $data can be anything, not only an object. a feature complete serializer should also handle string (trivial) and arrays (loop over the elements)
            // we can ignore this for now because product lists are ProductCollection objects, never plain PHP arrays

            return false;
        }
        if (null !== $this->enabledClasses && !\array_key_exists(\get_class($data), $this->enabledClasses)) {
            return false;
        }
        if (null !== $context) {
            if (!$context instanceof SerializationContext) {
                throw new \InvalidArgumentException(sprintf(
                    'Serialization context for %s needs to be an instance of %s, %s given',
                    __CLASS__,
                    SerializationContext::class,
                    \get_class($context)
                ));
            }

            if ($context->hasCustomExclusionStrategy()) {
                // Custom exclusion strategies are not supported
                return false;
            }
        }

        return true;
    }

    private function useGeneratedDeserializer(string $type, ?DeserializationContext $context): bool
    {
        if (null !== $this->enabledClasses && !\array_key_exists($type, $this->enabledClasses)) {
            return false;
        }
        if (!$context) {
            return true;
        }
        if ($context->hasAttribute('version')) {
            return false;
        }
        if ($context->hasAttribute('groups') && \count($context->getAttribute('groups'))) {
            return false;
        }

        return true;
    }
}
