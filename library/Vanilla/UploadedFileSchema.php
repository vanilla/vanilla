<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla;

use Gdn;
use Gdn_Upload;
use Garden\Schema\Invalid;
use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use Garden\Schema\ValidationException;
use Mimey\MimeTypes;

/**
 * Validation for uploaded files.
 */
class UploadedFileSchema extends Schema {
    protected const UNKNOWN_CONTENT_TYPE = "application/octet-stream";

    protected const TEXT_CONTENT_TYPE = "text/plain";

    const OPTION_ALLOWED_EXTENSIONS = 'allowedExtensions';
    const OPTION_MAX_SIZE = 'maxSize';
    const OPTION_VALIDATE_CONTENT_TYPES = 'validateContentTypes';
    const OPTION_ALLOW_UNKNOWN_TYPES = "allowUnknownTypes";

    /** @var int $maxFileSize */
    private $maxSize;

    /** @var array $extensions */
    private $allowedExtensions = [];

    /** @var bool */
    private $allowUnknownTypes = false;

    /** @var MimeTypes */
    private $mimeTypes;

    /**
     * @var bool Whether or not to validate mime types.
     */
    private $validateContentTypes = false;

    /**
     * Initialize an instance of a new UploadedFileSchema class.
     *
     * @param array $options
     */
    public function __construct(array $options = []) {
        $options += [
            self::OPTION_VALIDATE_CONTENT_TYPES => false,
        ];

        if (array_key_exists(self::OPTION_ALLOWED_EXTENSIONS, $options)) {
            $allowedExtensions = $options[self::OPTION_ALLOWED_EXTENSIONS];
        } else {
            $allowedExtensions = Gdn::getContainer()->get('Config')->get('Garden.Upload.AllowedFileExtensions', []);
        }

        if (array_key_exists(self::OPTION_MAX_SIZE, $options)) {
            $maxSize = $options[self::OPTION_MAX_SIZE];
        } else {
            $maxSize = Gdn_Upload::unformatFileSize(Gdn::getContainer()->get('Config')->get(
                'Garden.Upload.MaxFileSize',
                ini_get('upload_max_filesize')
            ));
        }

        $this->setValidateContentTypes($options[self::OPTION_VALIDATE_CONTENT_TYPES]);

        $this->setMaxSize($maxSize);
        $this->setAllowedExtensions(array_map('strtolower', $allowedExtensions));

        $this->mimeTypes = new MimeTypes();
        $this->setAllowUnknownTypes($options[self::OPTION_ALLOW_UNKNOWN_TYPES] ?? false);

        parent::__construct([
            'id' => 'UploadedFile',
            'type' => 'string',
            'format' => 'binary'
        ]);
    }

    /**
     * Get allowed file extensions.
     *
     * @return array
     */
    public function getAllowedExtensions() {
        return $this->allowedExtensions;
    }

    /**
     * Should unknown file content types be allowed?
     *
     * @return boolean
     */
    public function getAllowUnknownTypes(): bool {
        return $this->allowUnknownTypes;
    }

    /**
     * Get the maximum file size.
     *
     * @return int
     */
    public function getMaxSize() {
        return $this->maxSize;
    }

    /**
     * Set allowed file extensions.
     *
     * @param array $allowedExtensions
     * @return array
     */
    public function setAllowedExtensions(array $allowedExtensions) {
        return $this->allowedExtensions = $allowedExtensions;
    }

    /**
     * Set whether or not unknown file content types be allowed.
     *
     * @param bool $allowUnknownTypes
     * @return bool
     */
    public function setAllowUnknownTypes(bool $allowUnknownTypes): bool {
        return $this->allowUnknownTypes = $allowUnknownTypes;
    }

    /**
     * Set the maximum file size, in bytes.
     *
     * @param $maxSize
     * @return int
     */
    public function setMaxSize($maxSize) {
        return $this->maxSize = (int)$maxSize;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($data, $sparse = false) {
        $field = new ValidationField($this->createValidation(), $this->getSchemaArray(), '', $sparse);

        $clean = $this->validateUploadedFile($data, $field);

        if (Invalid::isInvalid($clean) && $field->isValid()) {
            // This really shouldn't happen, but we want to protect against seeing the invalid object.
            $field->addError('invalid', ['messageCode' => '{field} is invalid.', 'status' => 422]);
        }

        if (!$field->getValidation()->isValid()) {
            throw new ValidationException($field->getValidation());
        }

        return $data;
    }

    /**
     * Validate the upload's MIME content type.
     *
     * @param UploadedFile $upload
     * @param ValidationField $field
     * @param string $extension
     */
    protected function validateContentType(UploadedFile $upload, ValidationField $field, string $extension): void {
        $file = $upload->getFile();

        $detectedType = mime_content_type($file);
        if (!is_string($detectedType)) {
            $field->addError("invalid", ["messageCode" => "Content type of {field} cannot be detected."]);
            return;
        }

        $extension = strtolower($extension);
        $validExtensions = $this->mimeTypes->getAllExtensions($detectedType);

        if (in_array($extension, $validExtensions) || $detectedType === self::TEXT_CONTENT_TYPE) {
            return;
        }

        if (!empty($detectedType)) {
            $validTypes = $this->mimeTypes->getAllMimeTypes($extension);
            // Check to see if the mime type is part of the valid mime type string.
            // This code looks redundant, but sometimes mime_content_type() returns odd double strings.
            // ex: application/vnd.openxmlformats-officedocument.wordprocessingml.documentapplication/vnd.openxmlformats-officedocument.wordprocessingml.document
            foreach ($validTypes as $validType) {
                if (!empty($validType) && strpos($detectedType, $validType) !== false) {
                    return;
                }
            }
        }

        if ($detectedType === self::UNKNOWN_CONTENT_TYPE) {
            if (!$this->getAllowUnknownTypes()) {
                $field->addError("invalid", ["messageCode" => "The file has an unknown mime type."]);
            }
        } elseif (empty($validExtensions)) {
            trigger_error("No known mime type for extension: $extension.", E_USER_NOTICE);
        } else {
            $field->addError("invalid", [
                "messageCode" => "The file has an extension that is not valid for its content type. ({ext} does not match {mime})",
                "ext" => $extension,
                "mime" => $detectedType,
            ]);
        }
    }

    /**
     * Validate a file upload.
     *
     * @param mixed $value The value to validate.
     * @param ValidationField $field The validation results to add.
     * @return string|Invalid Returns the valid string or **null** if validation fails.
     */
    protected function validateUploadedFile($value, ValidationField $field) {
        if (!($value instanceof UploadedFile)) {
            $field->addError('invalid', ['messageCode' => '{field} is not a valid file upload.']);
        }
        /* @var UploadedFile $value */
        if ($this->validateExists($value, $field)) {
            $this->validateSize($value, $field);
            $this->validateExtension($value, $field);
        }

        if ($field->getErrorCount() > 0) {
            $value = Invalid::value();
        }
        return $value;
    }

    /**
     * Verify a file's alleged extension is allowed.
     *
     * @param UploadedFile $upload
     * @param ValidationField $field
     * @return UploadedFile
     */
    protected function validateExtension(UploadedFile $upload, ValidationField $field) {
        $result = false;
        $file = $upload->getClientFilename();

        if (is_string($file) && $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
            $ext = strtolower($ext);
            if (in_array($ext, $this->getAllowedExtensions())) {
                if ($this->getValidateContentTypes()) {
                    $this->validateContentType($upload, $field, $ext);
                }
                $result = true;
            }
        } else {
            $ext = null;
        }

        if ($result !== true) {
            if ($ext === null) {
                $field->addError('invalid', ['messageCode' => '{field} does not contain a file extension.']);
            } else {
                $field->addError('invalid', ['messageCode' => '{field} contains an invalid file extension: {ext}.', 'ext' => $ext]);
            }
        }

        return $upload;
    }

    /**
     * Verify a file's size is beneath the maximum.
     *
     * @param UploadedFile $upload
     * @return UploadedFile
     */
    protected function validateSize(UploadedFile $upload, ValidationField $field) {
        if ($upload->getSize() > $this->getMaxSize()) {
            $field->addError('invalid', ['messageCode' => '{field} exceeds the maximum file size.']);
        }
        return $upload;
    }

    /**
     * Whether or not to validate mime types.
     *
     * @return bool Returns **true** if mime types are validated or **false** otherwise.
     */
    public function getValidateContentTypes(): bool {
        return $this->validateContentTypes;
    }

    /**
     * Whether or not to validate mime types.
     *
     * @param bool $validateContentTypes The new value.
     * @return $this
     */
    public function setValidateContentTypes(bool $validateContentTypes): self {
        $this->validateContentTypes = $validateContentTypes;
        return $this;
    }

    /**
     * Validate that an uploaded file exists.
     *
     * @param UploadedFile $file The file to test.
     * @param ValidationField $field The field to collect errors.
     * @return bool Returns **true** if the file validated or **false** otherwise.
     */
    protected function validateExists(UploadedFile $file, ValidationField $field): bool {
        if (!file_exists($file->getFile())) {
            $field->addError("required", ["messageCode" => "File doesn't exist."]);
            return false;
        }
        return true;
    }
}
