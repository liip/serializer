<?php

declare(strict_types=1);

namespace Liip\Serializer\Exception;

/**
 * Thrown when serializing to or deserializing from a type that is not known.
 *
 * As the Liip serializer has to generate a PHP file for each type it can
 * handle, this most likely means you missed this type in your configuration.
 */
final class UnsupportedTypeException extends Exception
{
    private const UNSUPPORTED_TYPE_DESERIALIZATION = 'Type "%s" is not known. This most likely means that you forgot to configure the generators to support this file, or that the generators did not run.';

    private const UNSUPPORTED_TYPE_SERIALIZATION = 'Type "%s" is not known in version %s and groups %s. This most likely means that you forgot to configure the generators to support this file with the specific combination of version and groups, or that the generators did not run.';

    public static function typeUnsupportedDeserialization(string $type): self
    {
        return new self(sprintf(self::UNSUPPORTED_TYPE_DESERIALIZATION, $type));
    }

    public static function typeUnsupportedSerialization(string $type, ?string $version, array $groups): self
    {
        $versionInfo = $version ?: '[no version]';
        $groupInfo = \count($groups) ? implode(', ', $groups) : '[no groups]';

        return new self(sprintf(self::UNSUPPORTED_TYPE_SERIALIZATION, $type, $versionInfo, $groupInfo));
    }
}
