<?php
/**
 * @copyright 2009-2017 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 */

/**
 * Class MediaModel
 */
class MediaModel extends Gdn_Model {

    /**
     * MediaModel constructor.
     */
    public function __construct() {
        parent::__construct('Media');
    }

    /**
     * Get a media row by ID.
     *
     * @param int $MediaID The ID of the media entry.
     * @param string $DatasetType The format of the result dataset.
     * @param array $Options options to pass to the database.
     * @return array|object|false Returns the media row or **false** if it isn't found.
     */
    public function getID($MediaID, $DatasetType = false, $Options = []) {
        $this->fireEvent('BeforeGetID');
        return parent::getID($MediaID, $DatasetType, $Options);
    }

    /**
     * Assing an attachment to another record.
     *
     * @param $ForeignID
     * @param $ForeignTable
     * @param $NewForeignID
     * @param $NewForeignTable
     * @return Gdn_Dataset
     */
    public function reassign($ForeignID, $ForeignTable, $NewForeignID, $NewForeignTable) {
        $this->fireEvent('BeforeReassign');
        return $this->update(
            ['ForeignID' => $NewForeignID, 'ForeignTable' => $NewForeignTable],
            ['ForeignID' => $ForeignID, 'ForeignTable' => $ForeignTable]
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
     * @param int $mediaID ID of the record to delete
     * @param array $options An array of options to control the delete.
     * - deleteFile: Delete the file from the disk. True by default
     *
     * @return Gdn_Dataset
     */
    public function deleteID($mediaID, $options = []) {
        return $this->delete(['MediaID' => $mediaID], $options);
    }

    /**
     * Te be removed soon™
     *
     * @deprecated
     *
     * @param $Media
     * @param $Options
     * @return bool|Gdn_DataSet|object|string|void
     */
    private function deprecatedDelete($Media, $Options) {
        if (is_bool($Options)) {
            $DeleteFile = $Options;
        } else {
            $lcOptions = array_change_key_case($Options, CASE_LOWER);
            $DeleteFile = val('delete', $lcOptions, true);
        }

        $MediaID = false;
        if (is_a($Media, 'stdClass')) {
            $Media = (array)$Media;
        }

        if (is_numeric($Media)) {
            $MediaID = $Media;
        }
        elseif (array_key_exists('MediaID', $Media)) {
            $MediaID = $Media['MediaID'];
        }

        if ($MediaID) {
            return $this->delete(['MediaID' => $MediaID], ['deleteFile' => $DeleteFile]);
        } else {
            return $this->SQL->delete($this->Name, $Media);
        }
    }

    /**
     * See deleteUsingParent().
     *
     * @deprecated
     *
     * @param $ParentTable
     * @param $ParentID
     */
    public function deleteParent($ParentTable, $ParentID) {
        deprecated(__METHOD__.'($ParentTable, $ParentID)', 'deleteUsingParent($recordType, $recordID)');
        $this->deleteUsingParent($ParentTable, $ParentID);
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
}
