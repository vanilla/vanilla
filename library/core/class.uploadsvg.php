<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Web\Exception\ClientException;
use Vanilla\FeatureFlagHelper;

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
        $expectedMimeTypes = ["image/svg+xml", "image/svg"];
        // Add some .svg-specific validation here.
        if (!in_array($_FILES[$inputName]["type"], $expectedMimeTypes)) {
            throw new ClientException(t("You must upload an .svg file."));
        }
        $tmpName = parent::validateUpload($inputName, $throwException);
        if (FeatureFlagHelper::featureEnabled("validateContentTypes")) {
            $fInfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $fInfo->file($tmpName);
            $mimeType = strtolower($mimeType);
            if (!in_array($mimeType, $expectedMimeTypes)) {
                if ($throwException) {
                    throw new Gdn_UserException(t("Your image file is not a valid image file."));
                }
                return false;
            }
        }

        return $tmpName;
    }

    /**
     * Check if the uploaded file is an svg.
     *
     * @param string $filename
     * @return bool
     */
    public static function isSvg(string $filename): bool
    {
        return isset($_FILES[$filename]) &&
            in_array(strtolower($_FILES[$filename]["type"]), ["image/svg+xml", "image/svg"]);
    }
}
