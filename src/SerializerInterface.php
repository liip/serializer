<?php

declare(strict_types=1);

namespace Liip\Serializer;

use Liip\Serializer\Exception\Exception;
use Liip\Serializer\Exception\UnsupportedFormatException;
use Liip\Serializer\Exception\UnsupportedTypeException;

/**
 * Contract of the Liip Serializer.
 *
 * This is mainly intended for mocking purposes in unit tests.
 */
interface SerializerInterface
{
    /**
     * Convert an object to a string representation.
     *
     * @param mixed    $data    The model to serialize.
     * @param string   $format  The target format to serialize to
     * @param ?Context $context Additional configuration for serialization
     *
     * @return string Encoded data according to $format
     *
     * @throws Exception                  if anything else goes wrong
     * @throws UnsupportedFormatException if $format is not supported
     * @throws UnsupportedTypeException   if no generated function is available for the class of $data
     */
    public function serialize(mixed $data, string $format, ?Context $context = null): string;

    /**
     * Convert a string representation to an object.
     *
     * @param string   $data    Encoded data according to $format
     * @param string   $type    The target type to deserialize to
     * @param string   $format  Encoding of $data
     * @param ?Context $context Additional configuration for deserialization
     *
     * @return mixed Object of $type
     *
     * @throws Exception                  if anything else goes wrong
     * @throws UnsupportedFormatException if $format is not supported
     * @throws UnsupportedTypeException   if there is no generated function available for $type
     */
    public function deserialize(string $data, string $type, string $format, ?Context $context = null): mixed;

    /**
     * Convert an object to an array.
     *
     * @param mixed    $data    The model to convert to an array.
     * @param ?Context $context Additional configuration for serialization
     *
     * @return mixed[] Data represented as an array
     *
     * @throws Exception                if anything else goes wrong
     * @throws UnsupportedTypeException if no generated function is available for the class of $data
     */
    public function toArray(mixed $data, ?Context $context = null): array;

    /**
     * Convert an array to an object.
     *
     * @param mixed[]  $data    Array representation of the model
     * @param string   $type    The target type to deserialize to
     * @param ?Context $context Additional configuration for deserialization
     *
     * @return mixed Object of $type
     *
     * @throws Exception                if anything else goes wrong
     * @throws UnsupportedTypeException if there is no generated function available for $type
     */
    public function fromArray(array $data, string $type, ?Context $context = null): mixed;
}
