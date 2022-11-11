<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Utility;

use Garden\Web\RequestInterface;
use Gdn;

/**
 * A collection of file utils.
 */
class FileGeneratorUtils
{
    /**
     * Generate a Content-Disposition for a file based on an extension.
     *
     * @param string $extension
     * @return string
     */
    public static function getContentDisposition(RequestInterface $request): string
    {
        $filename = self::generateFileName($request);
        return "attachment; filename=\"$filename\"";
    }

    /**
     * Generate a Content-Type for a file based on an extension.
     *
     * @param string $extension
     * @return string
     */
    public static function getContentType(string $extension): string
    {
        return "application/$extension; charset=utf-8";
    }

    /**
     * Get the extension of a request.
     *
     * @param RequestInterface $request
     * @return string
     */
    public static function getExtension(RequestInterface $request): string
    {
        $path = $request->getPath();
        return strtolower(pathinfo($path, PATHINFO_EXTENSION));
    }

    /**
     * Generate a fileName based on the query + current time.
     *
     * @param string $extension
     * @return string
     */
    public static function generateFileName(RequestInterface $request): string
    {
        $path = $request->getPath();
        $extension = self::getExtension($request);
        $fileName = str_replace(["/api/v2/", ".$extension", "/"], ["", "", "-"], $path);
        $fileName .= "-" . date("Ymd-His");
        return $fileName . "." . $extension;
    }
}
