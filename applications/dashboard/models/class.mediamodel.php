<?php
/**
 * @copyright 2009-2019 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 */

use Garden\Web\Exception\NotFoundException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Vanilla\Contracts\Web\FileUploadHandler;
use Vanilla\Models\VanillaMediaSchema;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerFailedID;
use Vanilla\Scheduler\LongRunnerItemResultInterface;
use Vanilla\Scheduler\LongRunnerNextArgs;
use Vanilla\Scheduler\LongRunnerQuantityTotal;
use Vanilla\Scheduler\LongRunnerSuccessID;
use Vanilla\Scheduler\LongRunnerTimeoutException;
use Vanilla\UploadedFile;
use Vanilla\Utility\ModelUtils;
use Vanilla\Web\SystemCallableInterface;

/**
 * Class MediaModel
 */
class MediaModel extends Gdn_Model implements FileUploadHandler, SystemCallableInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var Gdn_Upload */
    private $upload;

    /** @var int Bypass values set in ImageUpload.Limits config even if ImageUpload.Limits is enabled */
    const NO_IMAGE_DIMENSIONS_LIMIT = 0;

    /**
     * MediaModel constructor.
     */
    public function __construct()
    {
        parent::__construct("Media");
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
    public function getID($id, $datasetType = false, $options = [])
    {
        $this->fireEvent("BeforeGetID");
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
    public function reassign($foreignID, $foreignTable, $newForeignID, $newForeignTable)
    {
        $this->fireEvent("BeforeReassign");
        return $this->update(
            ["ForeignID" => $newForeignID, "ForeignTable" => $newForeignTable],
            ["ForeignID" => $foreignID, "ForeignTable" => $foreignTable]
        );
    }

    /**
     * Delete records from a table.
     *
     * @param array $where The where clause to delete or an integer value.
     * @param array $options An array of options to control the delete.
     * - limit: A limit to the number of records to delete.
     * - deleteFile: Delete the file from the disk. (If MediaID is provided in the where clause) False by default
     *
     * @return false|Gdn_Dataset|int
     */
    public function delete($where = [], $options = [])
    {
        $validWhere = is_array($where);
        $validOptions = is_array($options);

        if (!($validWhere && $validOptions)) {
            deprecated("MediaModel->delete(!array, !array)", "MediaModel->delete(array , array)");
            return $this->deprecatedDelete($where, $options);
        }

        // Implicitely
        if (val("deleteFile", $options, true)) {
            $mediaID = val("MediaID", $where, false);
            if ($mediaID) {
                $media = $this->getID($mediaID);

                $mediaPath = val("Path", $media);
                if (!empty($mediaPath)) {
                    $this->upload->delete($mediaPath);
                }

                $thumbPath = val("ThumbPath", $media);
                if (!empty($thumbPath)) {
                    $this->upload->delete($thumbPath);
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
    public function deleteID($id, $options = [])
    {
        return $this->delete(["MediaID" => $id], $options);
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
    private function deprecatedDelete($media, $options)
    {
        if (is_bool($options)) {
            $deleteFile = $options;
        } else {
            $lcOptions = array_change_key_case($options, CASE_LOWER);
            $deleteFile = val("delete", $lcOptions, true);
        }

        $mediaID = false;
        if (is_a($media, "stdClass")) {
            $media = (array) $media;
        }

        if (is_numeric($media)) {
            $mediaID = $media;
        } elseif (array_key_exists("MediaID", $media)) {
            $mediaID = $media["MediaID"];
        }

        if ($mediaID) {
            return $this->delete(["MediaID" => $mediaID], ["deleteFile" => $deleteFile]);
        } else {
            return $this->SQL->delete($this->Name, $media);
        }
    }

    /**
     * Normalize and validate a row.
     *
     * @param array $row
     *
     * @return array
     */
    private function normalizeAndValidate(array $row): array
    {
        return VanillaMediaSchema::normalizeFromDbRecord($row);
    }

    /**
     * @inheritdoc
     */
    public function saveUploadedFile(UploadedFile $file, array $extraArgs = []): array
    {
        $extraArgs += [
            "maxImageHeight" => self::NO_IMAGE_DIMENSIONS_LIMIT,
            "maxImageWidth" => self::NO_IMAGE_DIMENSIONS_LIMIT,
        ];

        if ($extraArgs["maxImageHeight"]) {
            $maxImageHeight =
                $extraArgs["maxImageHeight"] === self::NO_IMAGE_DIMENSIONS_LIMIT
                    ? $file::MAX_IMAGE_HEIGHT
                    : $extraArgs["maxImageHeight"];
            $file->setMaxImageHeight($maxImageHeight);
        }

        if ($extraArgs["maxImageWidth"]) {
            $maxImageWidth =
                $extraArgs["maxImageWidth"] === self::NO_IMAGE_DIMENSIONS_LIMIT
                    ? $file::MAX_IMAGE_WIDTH
                    : $extraArgs["maxImageWidth"];
            $file->setMaxImageWidth($maxImageWidth);
        }

        // Casen extra args for the DB.
        if (isset($extraArgs["foreignID"])) {
            $extraArgs["ForeignID"] = $extraArgs["foreignID"];
        }
        if (isset($extraArgs["foreignType"])) {
            $extraArgs["ForeignTable"] = $extraArgs["foreignType"];
        }

        $media = array_merge($extraArgs, [
            "Name" => $file->getClientFilename(),
            "Type" => $file->getClientMediaType(),
            "Size" => $file->getSize(),
        ]);

        if ($file->getForeignUrl() !== null) {
            $media["foreignUrl"] = $file->getForeignUrl();
        }

        // Persist the actual file an get it's final URL.
        // We might have already persisted the upload.
        $persistedPath = $file->getPersistedPath();
        if ($persistedPath === null) {
            $persistedPath = $file->persistUpload()->getPersistedPath();
        }
        $media["Path"] = $persistedPath;
        if ($file->getClientWidth() !== null) {
            $media["ImageWidth"] = $file->getClientWidth();
        }
        if ($file->getClientHeight() !== null) {
            $media["ImageHeight"] = $file->getClientHeight();
        }

        $id = $this->save($media);
        ModelUtils::validationResultToValidationException($this, \Gdn::locale(), true);

        $result = $this->findUploadedMediaByID($id);
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function findUploadedMediaByID(int $id): array
    {
        $row = $this->getID($id, DATASET_TYPE_ARRAY);
        if (!$row) {
            throw new NotFoundException("Media");
        }
        return $this->normalizeAndValidate($row);
    }

    /**
     * @inheritdoc
     */
    public function findUploadedMediaByUrl(string $url): array
    {
        $uploadPaths = $this->upload->getUploadWebPaths();

        $testPaths = [];
        foreach ($uploadPaths as $type => $urlPrefix) {
            if (stringBeginsWith($url, $urlPrefix)) {
                $path = trim(stringBeginsWith($url, $urlPrefix, true, true), "\\/");
                if (!empty($type)) {
                    $path = "$type/$path";
                }
                $testPaths[] = $path;
            }
        }

        if (empty($testPaths)) {
            throw new NotFoundException("Media");
        }

        // Any matches?.
        $row = $this->getWhere(["Path" => $testPaths], "", "asc", 1)->firstRow(DATASET_TYPE_ARRAY);

        // Couldn't find a match.
        if (empty($row)) {
            throw new NotFoundException("Media");
        }

        return $this->normalizeAndValidate($row);
    }

    /**
     * @inheritdoc
     */
    public function findUploadedMediaByForeignUrl(string $foreignUrl): array
    {
        $row = $this->getWhere(["ForeignUrl" => $foreignUrl], "", "asc", 1)->firstRow(DATASET_TYPE_ARRAY);

        // Couldn't find a match.
        if (empty($row)) {
            throw new NotFoundException("Media");
        }

        return $this->normalizeAndValidate($row);
    }

    /**
     * @inheritdoc
     */
    public static function getSystemCallableMethods(): array
    {
        return ["deleteMediasListIterator", "deleteMediasListURLsIterator"];
    }

    /**
     * Generator for deleting multiple medias using their ID's, which can be a long-running process.
     *
     * User with LongRunner::run* methods.
     *
     * @param array $mediaIDs
     * @param bool $deleteFile
     *
     * @return Generator<int, LongRunnerItemResultInterface, string, string|LongRunnerNextArgs>
     */
    public function deleteMediasListIterator(array $mediaIDs, bool $deleteFile): Generator
    {
        $completedMediaIDs = [];

        try {
            yield new LongRunnerQuantityTotal(
                function ($mediaIDs) {
                    $mediaIDs = array_unique($mediaIDs);
                    return count($mediaIDs);
                },
                [$mediaIDs]
            );

            foreach ($mediaIDs as $mediaID) {
                $this->deleteID($mediaID, ["deleteFile" => $deleteFile]);
                $completedMediaIDs[] = $mediaID;
                yield new LongRunnerSuccessID($mediaID);
            }
        } catch (LongRunnerTimeoutException $e) {
            return new LongRunnerNextArgs([array_diff($mediaIDs, $completedMediaIDs), $deleteFile]);
        }
        return LongRunner::FINISHED;
    }

    /**
     * Generator for deleting multiple medias using their URL's, which can be a long-running process.
     *
     * User with LongRunner::run* methods.
     *
     * @param array $urls
     * @param bool $deleteFile
     *
     * @return Generator<int, LongRunnerItemResultInterface, string, string|LongRunnerNextArgs>
     */
    public function deleteMediasListURLsIterator(array $urls, bool $deleteFile): Generator
    {
        $completedURLs = [];

        try {
            yield new LongRunnerQuantityTotal(
                function ($urls) {
                    $urls = array_unique($urls);
                    return count($urls);
                },
                [$urls]
            );

            foreach ($urls as $url) {
                if ($deleteFile) {
                    $fileDeleted = $this->upload->delete($url);
                }

                try {
                    $row = $this->findUploadedMediaByUrl($url);
                    $this->deleteID($row["mediaID"], ["deleteFile" => $deleteFile]);
                } catch (NotFoundException $e) {
                    $this->logger->info("File not found in the database.", [
                        "url" => $url,
                        "fileDeleted" => $fileDeleted ?? false,
                    ]);
                    yield new LongRunnerFailedID($url);
                }
                $completedURLs[] = $url;
                yield new LongRunnerSuccessID($url);
            }
        } catch (LongRunnerTimeoutException $e) {
            return new LongRunnerNextArgs([array_diff($urls, $completedURLs), $deleteFile]);
        }
        return LongRunner::FINISHED;
    }
}
