/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { logDebug } from "./debugUtils";

/** This should mirror extensions allowed in Vanilla\ImageResizer.php */
const IMAGE_REGEX = /^image\/(gif|jpe?g|png)/i;

/**
 * A filter for use with [].filter
 *
 * Matches only image image type files.
 * @private
 *
 * @param file - A File object.
 * @see https://developer.mozilla.org/en-US/docs/Web/API/File
 *
 * @returns Whether or not the file is an acceptable image
 */
export function isFileImage(file: File): boolean {
    if (IMAGE_REGEX.test(file.type)) {
        return true;
    }

    logDebug("Filtered out non-image file: ", file.name);
    return false;
}
