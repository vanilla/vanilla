/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { log } from "@core/utility";
import api from "@core/apiv2";

/**
 * A class for handling file uploads in Vanilla.
 *
 * Contains handlers for dragging and pasting and <input type="file">
 */
export default class FileUploader {
    /** This should mirror extensions allowed in Vanilla\ImageResizer.php */
    public static IMAGE_REGEX = /^image\/(gif|jpe?g|png)/i;

    /**
     * @param uploadStartCallback - The callback to fire when an uploaded has been initiated for some files.
     */
    constructor(private uploadStartCallback: (resultPromise: Promise<any>) => void) {}

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
    public imageFilter = (file: File): boolean => {
        if (FileUploader.IMAGE_REGEX.test(file.type)) {
            return true;
        }

        log("Filtered out non-image file: ", file.name);
        return false;
    };

    /**
     * Handler for an file being dragged and dropped.
     *
     * @param event - https://developer.mozilla.org/en-US/docs/Web/API/DragEvent
     */
    public dropHandler = (event: DragEvent) => {
        if (event.dataTransfer && event.dataTransfer.files && event.dataTransfer.files.length) {
            event.preventDefault();
            const files = Array.from(event.dataTransfer.files);

            // Currently only 1 file is supported.
            const mainFile = files[0];
            this.uploadFile(mainFile);
        }
    };

    /**
     * Handler for an file being pasted.
     *
     * @param event - https://developer.mozilla.org/en-US/docs/Web/API/DragEvent
     */
    public pasteHandler = (event: ClipboardEvent) => {
        if (event.clipboardData && event.clipboardData.items && event.clipboardData.items.length) {
            const files = Array.from(event.clipboardData.items)
                .map(item => (item.getAsFile ? item.getAsFile() : null))
                .filter(Boolean);

            if (files.length > 0) {
                event.preventDefault();
                // Currently only 1 file is supported.
                const mainFile = files[0];
                this.uploadFile(mainFile!);
            }
        }
    };

    /**
     * Upload a file using Vanilla's API v2.
     *
     * @param file - The file to upload.
     */
    public uploadFile(file: File) {
        if (!this.imageFilter(file)) {
            const error = new Error(
                `Unable to upload an image of type ${file.type}. Supported formats included .gif, .jpg and .png`,
            );
            this.uploadStartCallback(Promise.reject(error));

            return;
        }

        const data = new FormData();
        data.append("file", file, file.name);
        data.append("type", "image");

        const resultPromise = api.post("/media", data).then(result => {
            result.data.type = "image";
            return result.data;
        });

        this.uploadStartCallback(resultPromise);
    }
}
