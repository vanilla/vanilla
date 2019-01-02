<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
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
