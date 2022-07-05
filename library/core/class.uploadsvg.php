<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Web\Exception\ClientException;

/**
 * Class Gdn_UploadSvg
 */
class Gdn_UploadSvg extends Gdn_Upload
{
    /**
     * Gdn_UploadSvg constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        parent::clear();
        $this->_AllowedFileExtensions = ["svg"];
    }

    /**
     * Validate an svg upload.
     *
     * @param string $inputName Input to validate.
     * @param bool $throwException Whether to throw an exception.
     * @return string|null
     * @throws Gdn_UserException Throws an exception if file doesn't meet specs.
     */
    public function validateUpload($inputName, $throwException = true): ?string
    {
        // Add some .svg-specific validation here.
        if (!in_array($_FILES[$inputName]["type"], ["image/svg+xml", "image/svg"])) {
            throw new ClientException(t("You must upload an .svg file."));
        }
        $tmpName = parent::validateUpload($inputName, $throwException);
        return $tmpName;
    }
}
