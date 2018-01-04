<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Fixtures;

class FileUtils extends \Vanilla\FileUtils {

    /**
     * {@inheritdoc}
     */
    public function isUploadedFile($filename) {
        $uploadedFiles = array_column($_FILES, 'tmp_name');
        $result = in_array($filename, $uploadedFiles);
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function moveUploadedFile($filename, $destination) {
        $result = rename($filename, $destination);
        return $result;
    }
}
