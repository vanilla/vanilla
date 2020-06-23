<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\SafeCurl\Exception\InvalidURLException;
use Garden\Schema\Schema;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\ApiUtils;
use Vanilla\Contracts\ConfigurationInterface;
use \Vanilla\EmbeddedContent\EmbedService;
use Vanilla\FeatureFlagHelper;
use Vanilla\ImageResizer;
use Vanilla\Models\VanillaMediaSchema;
use Vanilla\UploadedFile;
use Vanilla\UploadedFileSchema;
use \Vanilla\EmbeddedContent\AbstractEmbed;

/**
 * API Controller for `/media`.
 */
class MediaApiController extends AbstractApiController {

    /**
     * @deprecated
     */
    const TYPE_IMAGE = 'image';

    /**
     * @deprecated
     */
    const TYPE_FILE = 'file';

    /** @var Schema */
    private $idParamSchema;

    /** @var MediaModel */
    private $mediaModel;

    /** @var EmbedService */
    private $embedService;

    /** @var ImageResizer */
    private $imageResizer;

    /** @var ConfigurationInterface */
    private $config;

    /** @var bool */
    private $resizeImages;

    /** @var ?int Max image upload height */
    private $maxImageHeight;

    /** @var ?int Max image upload width */
    private $maxImageWidth;

    /**
     * DI.
     * @inheritdoc
     */
    public function __construct(MediaModel $mediaModel, EmbedService $embedService, ImageResizer $imageResizer, ConfigurationInterface $config) {
        $this->mediaModel = $mediaModel;
        $this->embedService = $embedService;
        $this->imageResizer = $imageResizer;
        $this->config =  $config;
        $this->resizeImages = $this->config->get('ImageUpload.Limits.Enabled');
        $this->maxImageHeight = $this->config->get('ImageUpload.Limits.Height');
        $this->maxImageWidth = $this->config->get('ImageUpload.Limits.Width');
    }

    /**
     * Delete a media item by ID.
     *
     * @param int $id The media item's numeric ID.
     */
    public function delete($id) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this->idParamSchema()->setDescription('Delete a media item.');
        $row = $this->mediaModel->findUploadedMediaByID($id);
        if ($row['insertUserID'] !== $this->getSession()->UserID) {
            $this->permission('Garden.Community.Manage');
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

        $row = $this->mediaModel->findUploadedMediaByUrl($query['url']);
        if ($row['insertUserID'] !== $this->getSession()->UserID) {
            $this->permission('Garden.Community.Manage');
        }

        $this->mediaModel->deleteID($row['mediaID']);
    }

    /**
     * Given a media row, verify the current user has permissions to edit it.
     *
     * @param array $row
     */
    private function editPermission(array $row) {
        $insertUserID = $row["insertUserID"] ?? null;
        if ($this->getSession()->UserID === $insertUserID) {
            // Make sure we can still perform uploads.
            $this->permission("Garden.Uploads.Add");
        } else {
            $this->permission("Garden.Community.Manage");
        }
    }

    /**
     * Get a schema instance comprised of all available media fields.
     *
     * @return Schema
     */
    protected function fullSchema() {
        return new \Vanilla\Models\VanillaMediaSchema(true);
    }

    /**
     * Get a media item's information by ID.
     *
     * @param int $id The media item's numeric ID.
     * @return array
     * @throws NotFoundException If the media item could not be found.
     */
    public function get(int $id) {
        $this->permission('Garden.SignIn.Allow');

        $row = $this->mediaModel->findUploadedMediaByID($id);
        if ($row['insertUserID'] !== $this->getSession()->UserID) {
            $this->permission('Garden.Community.Manage');
        }
        return $row;
    }

    /**
     * Get a media item's information by its URL.
     *
     * @param array $query The request query.
     * @return array
     * @throws NotFoundException If the media item could not be found.
     */
    public function get_byUrl(array $query) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this->schema(['url:s' => 'Full URL to the item.'], 'in')->setDescription('Get a media item, using its URL.');

        $query = $in->validate($query);

        $row = $this->mediaModel->findUploadedMediaByUrl($query['url']);
        if ($row['insertUserID'] !== $this->getSession()->UserID) {
            $this->permission('Garden.Community.Manage');
        }
        return $row;
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

        $body = $in->validate($body);

        $original = $this->mediaModel->findUploadedMediaByID($id);
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
        $row = $this->mediaModel->findUploadedMediaByID($id);
        return $row;
    }

    /**
     * Upload a file and store it in GDN_Media against the current user with "embed" as the table.
     * Return information from the media row along with a full URL to the file.
     *
     * @param array $body The request body.
     * @return array
     */
    public function post(array $body) {
        $this->permission('Garden.Uploads.Add');

        $allowedExtensions = $this->config->get('Garden.Upload.AllowedFileExtensions', []);
        $uploadSchema = new UploadedFileSchema([
            UploadedFileSchema::OPTION_ALLOWED_EXTENSIONS => $allowedExtensions,
            UploadedFileSchema::OPTION_VALIDATE_CONTENT_TYPES => FeatureFlagHelper::featureEnabled('validateContentTypes'),
            UploadedFileSchema::OPTION_ALLOW_UNKNOWN_TYPES => true,
            UploadedFileSchema::OPTION_ALLOW_NON_STRICT_TYPES => true, // less strict because mime_content_type isn't super accurate
        ]);

        $in = $this->schema([
            'file' => $uploadSchema,
        ], 'in')->setDescription('Add a media item.');

        $body = $in->validate($body);
        $fileData = [
            'foreignType' => 'embed',
            'foreignID' => $this->getSession()->UserID
        ];

        if ($this->resizeImages) {
            //Bypass ImageUpload.Limits if user has 'Garden.Community.Manage' permission or higher
            $bypassUploadLimits = $this->getSession()->getPermissions()->hasRanked('Garden.Community.Manage');

            if ($bypassUploadLimits) {
                $fileData['maxImageHeight'] = MediaModel::NO_IMAGE_DIMENSIONS_LIMIT;
                $fileData['maxImageWidth'] = MediaModel::NO_IMAGE_DIMENSIONS_LIMIT;
            } else {
                $fileData['maxImageHeight'] = $this->maxImageHeight;
                $fileData['maxImageWidth'] = $this->maxImageWidth;
            }
        }

        $row = $this->mediaModel->saveUploadedFile($body['file'], $fileData);
        return $row;
    }

    /**
     * Scrape information from a URL.
     *
     * @param array $body The request body.
     * @return array
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

        try {
            $pageInfo = $this->embedService->createEmbedForUrl($body['url'], $body['force']);
        } catch (InvalidURLException $e) {
            throw new ClientException($e->getMessage());
        }

        $result = $out->validate($pageInfo);
        return $result;
    }
}
