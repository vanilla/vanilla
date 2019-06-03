<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Schema\Schema;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\ApiUtils;
use \Vanilla\EmbeddedContent\EmbedService;
use Vanilla\ImageResizer;
use Vanilla\UploadedFile;
use Vanilla\UploadedFileSchema;
use \Vanilla\EmbeddedContent\AbstractEmbed;

/**
 * API Controller for `/media`.
 */
class MediaApiController extends AbstractApiController {
    const TYPE_IMAGE = 'image';
    const TYPE_FILE = 'file';

    /** @var Schema */
    private $idParamSchema;

    /** @var MediaModel */
    private $mediaModel;

    /** @var EmbedService */
    private $embedService;

    /** @var ImageResizer */
    private $imageResizer;

    /** @var Config */
    private $config;

    /**
     * MediaApiController constructor.
     *
     * @param MediaModel $mediaModel
     * @param EmbedService $embedService
     */
    public function __construct(MediaModel $mediaModel, EmbedService $embedService, ImageResizer $imageResizer, Gdn_Configuration $config) {
        $this->mediaModel = $mediaModel;
        $this->embedService = $embedService;
        $this->imageResizer = $imageResizer;
        $this->config =  $config;
    }

    /**
     * Delete a media item by ID.
     *
     * @param $id The media item's numeric ID.
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
     * Delete a media item by its URL.
     *
     * @param array $query The request query.
     */
    public function delete_byUrl(array $query) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this->schema(['url:s' => 'Full URL to the item.'], 'in')->setDescription('Delete a media item, using its URL.');
        $out = $this->schema([], 'out');

        $in->validate($query);

        $row = $this->mediaByUrl($query['url']);
        if ($row['InsertUserID'] !== $this->getSession()->UserID) {
            $this->permission('Garden.Moderation.Manage');
        }

        $this->mediaModel->deleteID($row['MediaID']);
    }

    /**
     * Process a user upload and insert into the media table.
     *
     * @param UploadedFile $upload An object representing an uploaded file.
     * @param string $type The upload type (e.g. "image").
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
            case self::TYPE_IMAGE:
                $imageSize = getimagesize($file);
                if (is_array($imageSize)) {
                    $media['ImageWidth'] = $imageSize[0];
                    $media['ImageHeight'] = $imageSize[1];
                }
        }

        // image dimensions are higher than limit, it needs resizing
        if ($this->config->get("ImageUpload.Limits.Enabled")) {
            if ($media['ImageWidth'] > $this->config->get("ImageUpload.Limits.Width") ||
                $media['ImageHeight'] > $this->config->get("ImageUpload.Limits.Height")
            ) {
                $this->imageResizer->resize(
                    $file,
                    null,
                    [
                        "height" => $this->config->get("ImageUpload.Limits.Height"),
                        "width" => $this->config->get("ImageUpload.Limits.Width"), "crop" => false
                    ]
                );
            }
        }

        $ext = pathinfo(strtolower($upload->getClientFilename()), PATHINFO_EXTENSION);
        $destination = $this->generateUploadPath($ext, true);
        $uploadResult = $this->saveUpload($upload, $destination);
        $media['Path'] = $uploadResult['SaveName'];

        $id = $this->mediaModel->save($media);
        $this->validateModel($this->mediaModel);

        $result = $this->mediaByID($id);
        return $result;
    }

    /**
     * Given a media row, verify the current user has permissions to edit it.
     *
     * @param array $row
     */
    private function editPermission(array $row) {
        $insertUserID = $row["InsertUserID"] ?? null;
        if ($this->getSession()->UserID === $insertUserID) {
            // Make sure we can still perform uploads.
            $this->permission("Garden.Uploads.Add");
        } else {
            $this->permission("Garden.Moderation.Manage");
        }
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
            'width:i|n?' => 'Image width',
            'height:i|n?' => 'Image height',
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
     * @param int $id The media item's numeric ID.
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
     * Get a media item's information by its URL.
     *
     * @param $query The request query.
     * @return array
     * @throws NotFoundException if the media item could not be found.
     */
    public function get_byUrl(array $query) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this->schema(['url:s' => 'Full URL to the item.'], 'in')->setDescription('Get a media item, using its URL.');
        $out = $this->schema($this->fullSchema(), 'out');

        $query = $in->validate($query);

        $row = $this->mediaByUrl($query['url']);
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
     * Get a media row by its full URL.
     *
     * @param string $url The full media URL.
     * @throws NotFoundException if the media item could not be found.
     * @return array
     */
    public function mediaByUrl($url) {
        $uploadPaths = Gdn_Upload::urls();

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
        $row = $this->mediaModel->getWhere(
            ['Path' => $testPaths],
            '',
            'asc',
            1
        )->firstRow(DATASET_TYPE_ARRAY);

        // Couldn't find a match.
        if (empty($row)) {
            throw new NotFoundException('Media');
        }

        return $row;
    }

    /**
     * Normalize a database record to match the Schema definition.
     *
     * @param array $row Database record.
     * @return array Return a record, normalized for output.
     */
    public function normalizeOutput(array $row) {
        $row['foreignID'] = $row['ForeignID'] ?? null;
        $row['foreignType'] = $row['ForeignTable'] ?? null;

        if (array_key_exists('Path', $row)) {
            $parsed = Gdn_Upload::parse($row['Path']);
            $row['url'] = $parsed['Url'];

            $ext = pathinfo($row['url'], PATHINFO_EXTENSION);
            if (in_array($ext, array_keys(ImageResizer::getExtType()))) {
                $row['height'] = $row['ImageHeight'] ?? null;
                $row['width'] = $row['ImageWidth'] ?? null;
            }
        } else {
            $row['url'] = null;
        }

        $schemaRecord = ApiUtils::convertOutputKeys($row);
        return $schemaRecord;
    }

    /**
     * Update a media item's attachment to another record.
     *
     * @param int $id
     * @param array $body
     * @return array
     * @throws NotFoundException If the media item could not be found.
     * @throws Garden\Schema\ValidationException If input validation fails.
     * @throws Garden\Schema\ValidationException If output validation fails.
     */
    public function patch_attachment(int $id, array $body): array {
        $this->idParamSchema();
        $in = $this->schema([
            "foreignType" => [
                "description" => "Type of resource the media item will be attached to (e.g. comment).",
                "enum" => [
                    "embed",
                ],
                "type" => "string",
            ],
            "foreignID" => [
                "description" => "Unique ID of the resource this media item will be attached to.",
                "type" => "integer",
            ],
        ], ["articlesPatchAttachment", "in"])->setDescription("Update a media item's attachment to another record.");
        $out = $this->schema($this->fullSchema(), "out");

        $body = $in->validate($body);

        $original = $this->mediaByID($id);
        $this->editPermission($original);

        $canAttach = $this->getEventManager()->fireFilter(
            "canAttachMedia",
            ($body["foreignType"] === "embed" && $body["foreignID"] === $this->getSession()->UserID),
            $body["foreignType"],
            $body["foreignID"]
        );
        if ($canAttach !== true) {
            throw new ClientException("Unable to attach to this record. It may not exist or you may have improper permissions to access it.");
        }

        $this->mediaModel->update(
            [
                "ForeignID" => $body["foreignID"],
                "ForeignTable" => $body["foreignType"],
            ],
            ["MediaID" => $id]
        );
        $row = $this->mediaByID($id);
        $row = $this->normalizeOutput($row);
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Upload a file and store it in GDN_Media against the current user with "embed" as the table.
     * Return information from the media row along with a full URL to the file.
     *
     * @param array $body The request body.
     * @return arrayx
     */
    public function post(array $body) {
        $this->permission('Garden.Uploads.Add');

        $allowedExtensions = $this->config->get('Garden.Upload.AllowedFileExtensions', []);
        $uploadSchema = new UploadedFileSchema([
            'allowedExtensions' => $allowedExtensions,
        ]);

        $in = $this->schema([
            'file' => $uploadSchema,
        ],'in')->setDescription('Add a media item.');
        $out = $this->schema($this->fullSchema(), 'out');

        $body = $in->validate($body);

        $imageExtensions = array_keys(ImageResizer::getExtType());
        /** @var UploadedFile $file */
        $file = $body['file'];
        $extension = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION) ?? '';
        $type = in_array($extension, $imageExtensions) ? self::TYPE_IMAGE : self::TYPE_FILE;

        $row = $this->doUpload($body['file'], $type);

        $row = $this->normalizeOutput($row);
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Scrape information from a URL.
     *
     * @param array $body The request body.
     * @return array
     * @throws Exception
     * @throws \Garden\Schema\ValidationException
     * @throws \Garden\Web\Exception\HttpException
     * @throws \Vanilla\Exception\PermissionException
     */
    public function post_scrape(array $body) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this->schema([
            'url:s' => 'The URL to scrape.',
            'force:b?' => [
                'default' => false,
                'description' => 'Force the scrape even if the result is cached.'
            ]
        ], 'in')->setDescription('Scrape information from a URL.');
        $out = $this->schema(
            new \Vanilla\Utility\InstanceValidatorSchema(AbstractEmbed::class),
            'out'
        );

        $body = $in->validate($body);

        $pageInfo = $this->embedService->createEmbedForUrl($body['url'], $body['force']);

        $result = $out->validate($pageInfo);
        return $result;
    }
}
