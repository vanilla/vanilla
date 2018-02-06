<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0
 */

use Garden\Schema\Schema;
use Garden\Web\Exception\NotFoundException;
use Vanilla\ApiUtils;
use Vanilla\ImageResizer;
use Vanilla\UploadedFile;
use Vanilla\UploadedFileSchema;

/**
 * API Controller for `/media`.
 */
class MediaApiController extends AbstractApiController {

    /** @var Schema */
    private $idParamSchema;

    /** @var MediaModel */
    private $mediaModel;

    /**
     * MediaApiController constructor.
     *
     * @param MediaModel $mediaModel
     */
    public function __construct(MediaModel $mediaModel) {
        $this->mediaModel = $mediaModel;
    }

    /**
     * Delete a media item by ID.
     *
     * @param $id
     */
    public function delete($id) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this->idParamSchema()->setDescription('Delete a media item.');
        $out = $this->schema($this->fullSchema(), 'out');

        $row = $this->mediaByID($id);
        if ($row['InsertUserID'] !== $this->getSession()->UserID) {
            $this->permission('Garden.Moderation.Manage');
        }

        $this->mediaModel->deleteID($id);
    }

    /**
     * Process a user upload and insert into the media table..
     *
     * @param UploadedFile $upload
     * @throws Exception if there was an error encountered when saving the upload.
     * @return array
     */
    private function doUpload(UploadedFile $upload, $type) {
        $file = $upload->getFile();

        $media = [
            'Name' => $upload->getClientFilename(),
            'Type' => $upload->getClientMediaType(),
            'Size' => $upload->getSize(),
            'ForeignID' => $this->getSession()->UserID,
            'ForeignTable' => 'embed'
        ];

        switch ($type) {
            case 'image':
                $imageSize = getimagesize($file);
                if (is_array($imageSize)) {
                    $media['ImageWidth'] = $imageSize[0];
                    $media['ImageHeight'] = $imageSize[1];
                }
        }

        $ext = pathinfo(strtolower($upload->getClientFilename()), PATHINFO_EXTENSION);
        $destination = $this->generateUploadPath($ext, true);
        $uploadResult = $this->saveUpload($upload, $destination);
        $media['Path'] = $uploadResult['Name'];

        $id = $this->mediaModel->save($media);
        $this->validateModel($this->mediaModel);

        $result = $this->mediaByID($id);
        echo null;
        return $result;
    }

    /**
     * Get a schema instance comprised of all available media fields.
     *
     * @return Schema
     */
    protected function fullSchema() {
        $schema = Schema::parse([
            'mediaID:i' => 'The ID of the record.',
            'url:s' => 'The URL of the file.',
            'name:s' => 'The original filename of the upload.',
            'type:s' => 'MIME type',
            'size:i' =>'File size in bytes',
            'width:i|n' => 'Image width',
            'height:i|n' => 'Image height',
            'dateInserted:dt' => 'When the media item was created.',
            'insertUserID:i' => 'The user that created the media item.',
            'foreignType:s|n' => 'Table the media is linked to.',
            'foreignID:i|n' => 'The ID of the table.'
        ]);
        return $schema;
    }

    /**
     * Get a media item's information by ID.
     *
     * @param $id
     * @return array
     * @throws NotFoundException if the media item could not be found.
     */
    public function get($id) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this->idParamSchema()->setDescription('Get a media item.');
        $out = $this->schema($this->fullSchema(), 'out');

        $row = $this->mediaByID($id);
        if ($row['InsertUserID'] !== $this->getSession()->UserID) {
            $this->permission('Garden.Moderation.Manage');
        }

        $row = $this->normalizeOutput($row);
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Get an ID-only media record schema.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function idParamSchema($type = 'in') {
        if ($this->idParamSchema === null) {
            $this->idParamSchema = $this->schema(
                Schema::parse(['id:i' => 'The media ID.']),
                $type
            );
        }
        return $this->schema($this->idParamSchema, $type);
    }

    /**
     * Get a media row by its numeric ID.
     *
     * @param int $id The media ID.
     * @throws NotFoundException if the media item could not be found.
     * @return array
     */
    public function mediaByID($id) {
        $row = $this->mediaModel->getID($id, DATASET_TYPE_ARRAY);
        if (!$row) {
            throw new NotFoundException('Media');
        }
        return $row;
    }

    /**
     * Normalize a database record to match the Schema definition.
     *
     * @param array $row Database record.
     * @return array Return a Schema record.
     */
    public function normalizeOutput(array $row) {
        $row['foreignID'] = $row['ForeignID'] ?? null;
        $row['foreignType'] = $row['ForeignTable'] ?? null;
        $row['height'] = $row['ImageHeight'] ?? null;
        $row['width'] = $row['ImageWidth'] ?? null;

        if (array_key_exists('Path', $row)) {
            $parsed = Gdn_Upload::parse($row['Path']);
            $row['url'] = $parsed['Url'];
        } else {
            $row['url'] = null;
        }

        $schemaRecord = ApiUtils::convertOutputKeys($row);
        return $schemaRecord;
    }

    /**
     * Upload a file and store it in GDN_Media against the current user with "embed" as the table.
     * Return information from the media row along with a full URL to the file.
     *
     * @param array $body
     * @return array
     */
    public function post(array $body) {
        $this->permission('Garden.SignIn.Allow');

        $uploadSchema = new UploadedFileSchema([
            'allowedExtensions' => array_values(ImageResizer::getTypeExt())
        ]);

        $in = $this->schema([
            'file' => $uploadSchema,
            'type:s' => [
                'description' => 'The upload type.',
                'enum' => ['image']
            ]
        ],'in')->setDescription('Add a media item.');
        $out = $this->schema($this->fullSchema(), 'out');

        $body = $in->validate($body);

        $row = $this->doUpload($body['file'], $body['type']);

        $row = $this->normalizeOutput($row);
        $result = $out->validate($row);
        return $result;
    }
}
