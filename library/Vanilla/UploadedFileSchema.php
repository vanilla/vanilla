<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GNU GPLv2
 */

namespace Vanilla;

use Gdn;
use Gdn_Upload;
use Garden\Schema\Invalid;
use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use Garden\Schema\ValidationException;

class UploadedFileSchema extends Schema {

    /** @var \Gdn_Configuration $config */
    private $config;

    /** @var int $maxFileSize */
    private $maxSize;

    /** @var array $extensions */
    private $allowedExtensions = [];

    /**
     * Initialize an instance of a new UploadedFileSchema class.
     */
    public function __construct(array $options = []) {
        $this->config = Gdn::getContainer()->get('Config');

        if (array_key_exists('allowedExtensions', $options)) {
            $allowedExtensions = $options['allowedExtensions'];
        } else {
            $allowedExtensions = $this->config->get('Garden.Upload.AllowedFileExtensions', []);
        }

        if (array_key_exists('maxSize', $options)) {
            $maxSize = $options['maxSize'];
        } else {
            $maxSize = Gdn_Upload::unformatFileSize($this->config->get('Garden.Upload.MaxFileSize', ini_get('upload_max_filesize')));
        }

        $this->setMaxSize($maxSize);
        $this->setAllowedExtensions(array_map('strtolower', $allowedExtensions));

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
        $this->validateSize($value, $field);
        $this->validateExtension($value, $field);

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

        if (is_string($file) && $ext = pathinfo($file, PATHINFO_EXTENSION)) {
            $ext = strtolower($ext);
            if (in_array($ext, $this->getAllowedExtensions())) {
                $result = true;
            }
        }

        if ($result !== true) {
            $field->addError('invalid', ['messageCode' => '{field} is not an allowed upload type.']);
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
}
