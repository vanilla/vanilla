<?php
/**
 * @license MIT https://github.com/elephox-dev/mimey/blob/develop/license
 */

namespace Vanilla\Web;

use Vanilla\FileUtils;

/**
 * Class for detecting mimetypes.
 *
 * Data from https://github.com/elephox-dev/mimey/blob/develop/dist/mime.types.json
 */
final class MimeTypeDetector
{
    /**
     * Get all known extensions for a particular mimetype.
     *
     * @param string $mimeType
     *
     * @return string[]
     */
    public static function getExtensionsForMimeType(string $mimeType): array
    {
        $mimeType = mb_strtolower($mimeType);
        $value = self::loadMimeToExtensionMapping()[$mimeType] ?? [];
        return $value;
    }

    /**
     * Get all known mime types for a particular extension.
     *
     * @param string $extension
     *
     * @return string[]
     */
    public static function getMimesForExtension(string $extension): array
    {
        $extension = mb_strtolower($extension);
        $value = self::loadExtensionToMimeMapping()[$extension] ?? [];
        return $value;
    }

    ///
    /// Internal
    ///

    /**
     * Load all the data from the json file.
     */
    private static function loadAll(): array
    {
        return FileUtils::getArray(__DIR__ . "/MimeTypes.json");
    }

    /**
     * @return array<string, string[]>
     */
    private static function loadMimeToExtensionMapping(): array
    {
        return self::loadAll()["extensions"];
    }

    /**
     * @return array<string, string[]>
     */
    private static function loadExtensionToMimeMapping(): array
    {
        return self::loadAll()["mimes"];
    }
}
