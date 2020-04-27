<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts\Web;

use Garden\Web\Exception\NotFoundException;
use Vanilla\UploadedFile;

/**
 * Interface for saving uploaded files.
 */
interface FileUploadHandler {

    /**
     * Save an uploaded file into the database and into the
     *
     * @param UploadedFile $file
     * @param array $extraArgs Extra arguments to save into the record.
     *        -
     *
     * @return array An array of data matching VanillaMediaSchema
     */
    public function saveUploadedFile(UploadedFile $file, array $extraArgs = []): array;

    /**
     * Find a saved media upload by it's media ID.
     *
     * @param int $id The ID of the uploaded resource.
     * @return array An array of data matching VanillaMediaSchema
     * @throws NotFoundException If the resource can't be found.
     */
    public function findUploadedMediaByID(int $id): array;

    /**
     * Find a saved media upload by it's application URL.
     *
     * @param string $url The web URL of the uploaded resource.
     * @return array An array of data matching VanillaMediaSchema
     * @throws NotFoundException If the resource can't be found.
     */
    public function findUploadedMediaByUrl(string $url): array;

    /**
     * Find a saved media upload by a foreign URL.
     *
     * @param string $foreignUrl The foreign URL of the media item.
     * @return array An array of data matching VanillaMediaSchema
     * @throws NotFoundException If the resource can't be found.
     */
    public function findUploadedMediaByForeignUrl(string $foreignUrl): array;
}
