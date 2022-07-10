<?php
/**
 * @copyright 2009-2019 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 */

use Garden\Web\Exception\NotFoundException;
use Vanilla\Contracts\Web\FileUploadHandler;
use Vanilla\Models\VanillaMediaSchema;
use Vanilla\UploadedFile;
use Vanilla\Utility\ModelUtils;

/**
 * Class MediaModel
 */
class MediaModel extends Gdn_Model implements FileUploadHandler {

    /** @var Gdn_Upload */
    private $upload;

    /** @var int Bypass values set in ImageUpload.Limits config even if ImageUpload.Limits is enabled */
    const NO_IMAGE_DIMENSIONS_LIMIT = 0;

    /**
     * MediaModel constructor.
     */
    public function __construct() {
        parent::__construct('Media');
        $this->upload = \Gdn::getContainer()->get(Gdn_Upload::class);
    }

    /**
     * Get a media row by ID.
     *
     * @param int $id The ID of the media entry.
     * @param string|false $datasetType The format of the result dataset.
     * @param array $options options to pass to the database.
     * @return array|object|false Returns the media row or **false** if it isn't found.
     */
    public function getID($id, $datasetType = false, $options = []) {
        $this->fireEvent('BeforeGetID');
        return parent::getID($id, $datasetType, $options);
    }

    /**
     * Assing an attachment to another record.
     *
     * @param int $foreignID
     * @param string $foreignTable
     * @param int $newForeignID
     * @param string $newForeignTable
     * @return bool
     */
    public function reassign($foreignID, $foreignTable, $newForeignID, $newForeignTable) {
        $this->fireEvent('BeforeReassign');
        return $this->update(
            ['ForeignID' => $newForeignID, 'ForeignTable' => $newForeignTable],
            ['ForeignID' => $foreignID, 'ForeignTable' => $foreignTable]
        );
    }

    /**
     * Delete records from a table.
     *
     * @param array $where The where clause to delete or an integer value.
     * @param array $options An array of options to control the delete.
     * - limit: A limit to the number of records to delete.
     * - deleteFile: Delete the file from the disk. (If MediaID is provided in the where clause) True by default
     *
     * @return Gdn_Dataset
     */
    public function delete($where = [], $options = []) {
        $validWhere = is_array($where);
        $validOptions = is_array($options);

        if (!($validWhere && $validOptions)) {
            deprecated('MediaModel->delete(!array, !array)', 'MediaModel->delete(array , array)');
            return $this->deprecatedDelete($where, $options);
        }

        // Implicitely
        if (val('deleteFile', $options, true)) {
            $mediaID = val('MediaID', $where, false);
            if ($mediaID) {
                $media = $this->getID($mediaID);

                $uploadPath = (defined('PATH_LOCAL_UPLOADS') ? PATH_LOCAL_UPLOADS : PATH_UPLOADS).'/';

                $mediaPath = val('Path', $media);
                if (!empty($mediaPath)) {
                    $filePath = $uploadPath.$mediaPath;
                    if (file_exists($filePath)) {
                        safeUnlink($filePath);
                    }
                }

                $thumbPath = val('ThumbPath', $media);
                if (!empty($thumbPath)) {
                    $filePath = $uploadPath.$thumbPath;
                    if (file_exists($filePath)) {
                        safeUnlink($filePath);
                    }
                }


            }
        }

        return parent::delete($where, $options);
    }

    /**
     * Delete record by ID.
     *
     * @param int $id ID of the record to delete
     * @param array $options An array of options to control the delete.
     * - deleteFile: Delete the file from the disk. True by default
     *
     * @return Gdn_Dataset
     */
    public function deleteID($id, $options = []) {
        return $this->delete(['MediaID' => $id], $options);
    }

    /**
     * Te be removed soon™
     *
     * @deprecated
     *
     * @param $media
     * @param $options
     * @return bool|Gdn_DataSet|object|string|void
     */
    private function deprecatedDelete($media, $options) {
        if (is_bool($options)) {
            $deleteFile = $options;
        } else {
            $lcOptions = array_change_key_case($options, CASE_LOWER);
            $deleteFile = val('delete', $lcOptions, true);
        }

        $mediaID = false;
        if (is_a($media, 'stdClass')) {
            $media = (array)$media;
        }

        if (is_numeric($media)) {
            $mediaID = $media;
        }
        elseif (array_key_exists('MediaID', $media)) {
            $mediaID = $media['MediaID'];
        }

        if ($mediaID) {
            return $this->delete(['MediaID' => $mediaID], ['deleteFile' => $deleteFile]);
        } else {
            return $this->SQL->delete($this->Name, $media);
        }
    }

    /**
     * See deleteUsingParent().
     *
     * @deprecated
     *
     * @param $parentTable
     * @param $parentID
     */
    public function deleteParent($parentTable, $parentID) {
        deprecated(__METHOD__.'($ParentTable, $ParentID)', 'deleteUsingParent($recordType, $recordID)');
        $this->deleteUsingParent($parentTable, $parentID);
    }

    /**
     * Delete all media items linked to a record.
     *
     * @param string $recordType Parent record type (Comment, Discussion..)
     * @param int $recordID Parent record ID
     */
    public function deleteUsingParent($recordType, $recordID) {
        $mediaItems = $this->getWhere([
            'ForeignTable' => $recordType,
            'ForeignID' => $recordID,
        ])->resultArray();

        foreach ($mediaItems as $media) {
            // Explicitly set the deleteFile option
            $this->delete($media['MediaID'], ['deleteFile' => true]);
        }
    }

    /**
     * Normalize and validate a row.
     *
     * @param array $row
     *
     * @return array
     */
    private function normalizeAndValidate(array $row): array {
        return VanillaMediaSchema::normalizeFromDbRecord($row);
    }

    /**
     * @inheritdoc
     */
    public function saveUploadedFile(UploadedFile $file, array $extraArgs = []): array {
        $extraArgs += [
            'maxImageHeight' => self::NO_IMAGE_DIMENSIONS_LIMIT,
            'maxImageWidth' => self::NO_IMAGE_DIMENSIONS_LIMIT,
        ];

        if ($extraArgs['maxImageHeight']) {
            $maxImageHeight = $extraArgs['maxImageHeight'] === self::NO_IMAGE_DIMENSIONS_LIMIT ?
                $file::MAX_IMAGE_HEIGHT :
                $extraArgs['maxImageHeight'];
            $file->setMaxImageHeight($maxImageHeight);
        }

        if ($extraArgs['maxImageWidth']) {
            $maxImageWidth = $extraArgs['maxImageWidth'] === self::NO_IMAGE_DIMENSIONS_LIMIT ?
                $file::MAX_IMAGE_WIDTH :
                $extraArgs['maxImageWidth'];
            $file->setMaxImageWidth($maxImageWidth);
        }

        // Casen extra args for the DB.
        if (isset($extraArgs['foreignID'])) {
            $extraArgs['ForeignID'] = $extraArgs['foreignID'];
        }
        if (isset($extraArgs['foreignType'])) {
            $extraArgs['ForeignTable'] = $extraArgs['foreignType'];
        }

        $media = array_merge($extraArgs, [
            'Name' => $file->getClientFilename(),
            'Type' => $file->getClientMediaType(),
            'Size' => $file->getSize(),
        ]);

        if ($file->getForeignUrl() !== null) {
            $media['foreignUrl'] = $file->getForeignUrl();
        }

        // Persist the actual file an get it's final URL.
        // We might have already persisted the upload.
        $persistedPath = $file->getPersistedPath();
        if ($persistedPath === null) {
            $persistedPath = $file->persistUpload()->getPersistedPath();
        }
        $media['Path'] = $persistedPath;
        if ($file->getClientWidth() !== null) {
            $media['ImageWidth'] = $file->getClientWidth();
        }
        if ($file->getClientHeight() !== null) {
            $media['ImageHeight'] = $file->getClientHeight();
        }

        $id = $this->save($media);
        ModelUtils::validationResultToValidationException($this, \Gdn::locale(), true);

        $result = $this->findUploadedMediaByID($id);
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function findUploadedMediaByID(int $id): array {
        $row = $this->getID($id, DATASET_TYPE_ARRAY);
        if (!$row) {
            throw new NotFoundException('Media');
        }
        return $this->normalizeAndValidate($row);
    }

    /**
     * @inheritdoc
     */
    public function findUploadedMediaByUrl(string $url): array {
        $uploadPaths = $this->upload->getUploadWebPaths();

        $testPaths = [];
        foreach ($uploadPaths as $type => $urlPrefix) {
            if (stringBeginsWith($url, $urlPrefix)) {
                $path = trim(stringBeginsWith($url, $urlPrefix, true, true), '\\/');
                if (!empty($type)) {
                    $path = "$type/$path";
                }
                $testPaths[] = $path;
            }
        }

        if (empty($testPaths)) {
            throw new NotFoundException('Media');
        }

        // Any matches?.
        $row = $this->getWhere(
            ['Path' => $testPaths],
            '',
            'asc',
            1
        )->firstRow(DATASET_TYPE_ARRAY);

        // Couldn't find a match.
        if (empty($row)) {
            throw new NotFoundException('Media');
        }

        return $this->normalizeAndValidate($row);
    }

    /**
     * @inheritdoc
     */
    public function findUploadedMediaByForeignUrl(string $foreignUrl): array {
        $row = $this->getWhere(
            ['ForeignUrl' => $foreignUrl],
            '',
            'asc',
            1
        )->firstRow(DATASET_TYPE_ARRAY);

        // Couldn't find a match.
        if (empty($row)) {
            throw new NotFoundException('Media');
        }

        return $this->normalizeAndValidate($row);
    }
}
