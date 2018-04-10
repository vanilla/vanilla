<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GNU GPLv2
 */

namespace Vanilla;

use InvalidArgumentException;
use RuntimeException;
use Gdn_Upload;

/**
 * Value object representing a file uploaded through an HTTP request.
 */
class UploadedFile {

    /** @var string */
    private $clientFileName;

    /** @var string */
    private $clientMediaType;

    /** @var int */
    private $error;

    /** @var string */
    private $file;

    /** @var string */
    private $moved = false;

    /** @var int */
    private $size;

    /** @var  Gdn_Upload */
    private $uploadModel;

    /**
     * UploadedFile constructor.
     *
     * @param Gdn_Upload $uploadModel
     * @param string $file
     * @param int $size
     * @param int $error
     * @param string|null $clientFileName
     * @param string|null $clientMediaType
     */
    public function __construct(Gdn_Upload $uploadModel, $file, $size, $error, $clientFileName = null, $clientMediaType = null) {
        $this->uploadModel = $uploadModel;

        $this->setSize($size);
        $this->setError($error);
        $this->setClientFileName($clientFileName);
        $this->setClientMediaType($clientMediaType);

        if ($this->getError() === UPLOAD_ERR_OK) {
            $this->setFile($file);
        }
    }

    /**
     * Retrieve a stream representing the uploaded file.
     *
     * @throws RuntimeException
     */
    public function getStream() {
        throw new RuntimeException(self::class.'::'.__FUNCTION__.' is not supported.');
    }

    /**
     * Move the uploaded file to a new location.
     *
     * @param string $targetPath Path to which to move the uploaded file.
     * @throws InvalidArgumentException if the $targetPath specified is invalid.
     * @throws RuntimeException on any error during the move operation.
     * @throws RuntimeException on the second or subsequent call to the method.
     */
    public function moveTo($targetPath) {
        if ($this->moved) {
            throw new RuntimeException('This upload has already been moved.');
        }

        $directory = dirname($targetPath);
        if (!is_writable($directory)) {
            throw new InvalidArgumentException('The specified path is not writable.');
        }

        if (!is_uploaded_file($this->file)) {
            throw new RuntimeException("'{$this->file}' is not a valid upload.");
        }

        try {
            $this->uploadModel->saveAs($this->file, $targetPath);
        } catch (\Exception $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode());
        }

        $this->moved = true;
    }

    /**
     * Retrieve the file size.
     *
     * @return int|null The file size in bytes or null if unknown.
     */
    public function getSize() {
        return $this->size;
    }

    /**
     * Retrieve the error associated with the uploaded file.
     *
     * @return int One of the UPLOAD_ERR_XXX constants.
     */
    public function getError() {
        return $this->error;
    }

    /**
     * Get the temporary filename associated with this uploaded file.
     *
     * @return string
     */
    public function getFile() {
        return $this->file;
    }

    /**
     * Retrieve the filename sent by the client.
     *
     * @return string|null The filename sent by the client or null if none was provided.
     */
    public function getClientFilename() {
        return $this->clientFileName;
    }

    /**
     * Retrieve the media type sent by the client.
     *
     * @return string|null The media type sent by the client or null if none was provided.
     */
    public function getClientMediaType() {
        return $this->clientMediaType;
    }

    /**
     * Set the client-provided file name.
     *
     * @param string|null $clientFileName
     * @throws InvalidArgumentException if the value is invalid.
     */
    private function setClientFileName($clientFileName) {
        if ($clientFileName === null || is_string($clientFileName)) {
            $this->clientFileName = $clientFileName;
        } else {
            throw new InvalidArgumentException('Client file name must be a string or null.');
        }
    }

    /**
     * Set the client-provided media type.
     *
     * @param string|null $clientMediaType
     * @throws InvalidArgumentException if the value is invalid.
     */
    private function setClientMediaType($clientMediaType) {
        if ($clientMediaType === null || is_string($clientMediaType)) {
            $this->clientMediaType = $clientMediaType;
        } else {
            throw new InvalidArgumentException('Client media type must be a string or null.');
        }
    }

    /**
     * Set the error flag.
     *
     * @param int $error An error flag. Must be one of the UPLOAD_ERR_* constants.
     * @throws InvalidArgumentException if the value is invalid.
     */
    private function setError($error) {
        $validErrors = [
            UPLOAD_ERR_OK,
            UPLOAD_ERR_INI_SIZE,
            UPLOAD_ERR_FORM_SIZE,
            UPLOAD_ERR_PARTIAL,
            UPLOAD_ERR_NO_FILE,
            UPLOAD_ERR_NO_TMP_DIR,
            UPLOAD_ERR_CANT_WRITE,
            UPLOAD_ERR_EXTENSION
        ];

        if (is_integer($error) && in_array($error, $validErrors)) {
            $this->error = $error;
        } else {
            throw new InvalidArgumentException('Error must be one of the UPLOAD_ERR_* constants.');
        }
    }

    /**
     * Set the temporary filename.
     *
     * @param string $file
     * @throws InvalidArgumentException if the value is invalid.
     */
    private function setFile($file) {
        if (is_string($file)) {
            $this->file = $file;
        } else {
            throw new InvalidArgumentException('File name must be a string.');
        }
    }

    /**
     * Set the file size.
     *
     * @param int $size The size of the file, in bytes.
     * @throws InvalidArgumentException if the value is invalid.
     */
    private function setSize($size) {
        if (is_int($size)) {
            $this->size = $size;
        } else {
            throw new InvalidArgumentException('Size must be an integer.');
        }
    }
}
