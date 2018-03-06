/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { log } from "@core/utility";
import ajax from "@core/ajax";

/**
 * A callback for events on the FileUploader class.
 *
 * @callback FileUploader~uploadStartCallback
 * @param {File[]} files - Files that are being uploaded through on of the handlers.
 */

/**
 * A class for handling file uploads in Vanilla.
 *
 * Contains handlers for dragging and pasting and <input type="file">
 */
export default class FileUploader {

    /** This should mirror extensions allowed in Vanilla\ImageResizer.php */
    static IMAGE_REGEX = /^image\/(gif|jpe?g|a?png|bmp|tiff|ico)/i;

    uploadStartCallback = () => {};
    uploadSuccessCallback = () => {};
    uploadFailureCallback = () => {};

    /**
     * @param {function(File): void} uploadStartCallback - The callback to fire when an uploaded has been initiated for some files.
     * @param {function(File, Object): void} uploadSuccessCallback - The callback to fire when an item has been successfully uploaded.
     * @param {function(File, Error): void} uploadFailureCallback - The callback to fire when an upload for an item has failed.
     */
    constructor(uploadStartCallback, uploadSuccessCallback, uploadFailureCallback) {
        uploadStartCallback && (this.uploadStartCallback = uploadStartCallback);
        uploadSuccessCallback && (this.uploadSuccessCallback = uploadSuccessCallback);
        uploadFailureCallback && (this.uploadFailureCallback = uploadFailureCallback);
    }

    /**
     * A filter for use with [].filter
     *
     * Matches only image image type files.
     * @private
     *
     * @param {File} file - A File object.
     * @see https://developer.mozilla.org/en-US/docs/Web/API/File
     *
     * @returns {boolean} - Whether or not the file is an acceptable image
     */
    imageFilter = (file) => {
        if (FileUploader.IMAGE_REGEX.test(file.type)) {
            return true;
        }

        log("Filtered out non-image file: ", file.name);
        return false;
    };

    /**
     * Handler for an file being dragged and dropped.
     *
     * @param {DragEvent} event - https://developer.mozilla.org/en-US/docs/Web/API/DragEvent
     */
    dropHandler = (event) => {
        if (event.dataTransfer && event.dataTransfer.files && event.dataTransfer.files.length) {
            const filteredFiles = Array
                .from(event.dataTransfer.files)
                .filter(this.imageFilter);

            if (filteredFiles.length > 0) {
                event.preventDefault();
            }

            // Currently only 1 file is supported.
            const mainFile = filteredFiles[0];
            this.uploadFile(mainFile);
        }
    };

    /**
     * Handler for an file being pasted.
     *
     * @param {DragEvent} event - https://developer.mozilla.org/en-US/docs/Web/API/DragEvent
     */
    pasteHandler = (event) => {
        if (event.clipboardData && event.clipboardData.items && event.clipboardData.items.length) {
            const files = Array
                .from(event.clipboardData.items)
                .map(item => item.getAsFile ? item.getAsFile() : item)
                .filter(Boolean);

            const filteredFiles = [].filter.call(files, this.imageFilter);
            if (filteredFiles.length > 0) {
                event.preventDefault();
            }

            // Currently only 1 file is supported.
            const mainFile = filteredFiles[0];
            this.uploadFile(mainFile);
        }
    };

    /**
     * Upload a file using Vanilla's API v2.
     *
     * @param {File} file - The file to upload.
     */
    uploadFile(file) {
        this.uploadStartCallback(file);
        const data = new FormData();
        data.append("file", file, file.name);
        data.append("type", "image");

        ajax.post("/media", data)
            .then(result => {
                this.uploadSuccessCallback(file, result);
            }).catch(error => {
                this.uploadFailureCallback(file, error);
            });
    }
}
