<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Controllers\Api;

use AbstractApiController;
use AttachmentModel;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\ForbiddenException;
use Vanilla\Community\Events\TicketEscalationEvent;
use Vanilla\Dashboard\Models\AttachmentService;
use Garden\Utils\ArrayUtils;
use Garden\Web\Data;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Dashboard\Models\AttachmentProviderInterface;

/**
 * API v2 endpoints for attachments.
 */
class AttachmentsApiController extends AbstractApiController
{
    private AttachmentModel $attachmentModel;

    private AttachmentService $attachmentService;

    /**
     * D.I.
     *
     * @param AttachmentModel $attachmentModel
     * @param AttachmentService $attachmentService
     */
    public function __construct(AttachmentModel $attachmentModel, AttachmentService $attachmentService)
    {
        $this->attachmentModel = $attachmentModel;
        $this->attachmentService = $attachmentService;
    }

    /**
     * Post a new attachment.
     *
     * @param array $body
     * @return Data
     * @throws \Garden\Web\Exception\NotFoundException
     */
    public function post(array $body): Data
    {
        $this->permission();
        $attachment = $this->attachmentService->createAttachment($body);

        $attachment = $this->normalizeSingleAttachment($attachment);

        $out = AttachmentModel::getAttachmentSchema();
        $out->validate($attachment);

        return new Data($attachment);
    }

    /**
     * Get attachments for a record.
     *
     * @param array $query
     * @return Data
     */
    public function index(array $query = []): \Garden\Web\Data
    {
        // Record-specific permissions are checked in the AttachmentService->normalizeAttachments() method.
        $this->permission();

        $in = $this->schema([
            "recordType:s" => ["enum" => ["discussion", "comment", "user"]],
            "recordID:i",
        ]);

        $in->validate($query);

        $foreignID = $this->attachmentModel->createForeignID($query["recordType"], $query["recordID"]);

        $attachments = $this->attachmentModel->getWhere(["ForeignID" => $foreignID])->resultArray();
        $attachments = $this->attachmentService->normalizeAttachments($attachments);

        $out = $this->schema([":a" => $this->attachmentModel->getAttachmentSchema()], "out");
        $out->validate($attachments);

        return new Data($attachments);
    }

    /**
     * Fetch the attachment schemas of the available external providers.
     *
     * @return Data
     * @deprecated Get the catalog from the SiteMetaExtra instead.
     */
    public function get_catalog(): Data
    {
        $this->permission();
        $catalog = $this->attachmentService->getCatalog();
        return new Data($catalog);
    }

    /**
     * Fetch the form schema of a specific provider.
     *
     * @param array $query
     * @return Data
     */
    public function get_schema(array $query = []): Data
    {
        $in = $this->schema(["attachmentType:s", "recordType:s", "recordID:i", "projectID:i?", "issueTypeID:i?"]);
        $in->validate($query);

        $provider = $this->getProvider($query["attachmentType"]);

        if (!$provider) {
            throw new NotFoundException("No provider was found for this attachment source.");
        }

        if (!$provider->canCreateAttachmentForRecord($query["recordType"], $query["recordID"])) {
            throw new ForbiddenException("You do not have permission to use this provider.");
        }

        $baseSchema = $this->attachmentModel->getHydratedAttachmentPostSchema(
            $query["attachmentType"],
            $query["recordType"],
            $query["recordID"]
        );

        $providerSchema = $provider->getHydratedFormSchema($query["recordType"], $query["recordID"], $query);

        $schema = $baseSchema->merge($providerSchema);

        return new Data($schema);
    }

    /**
     * Accepts an array of attachmentIDs and refreshes the associated data using the external issue provider.
     *
     * @param array $body
     * @return Data
     * @throws ClientException
     */
    public function post_refresh(array $body = [])
    {
        $this->permission();

        $in = $this->schema([
            "attachmentIDs:a" => ["items" => "int"],
            "onlyStale:b?" => [
                "default" => true,
            ],
        ]);
        $body = $in->validate($body);
        $attachmentIDs = array_unique($body["attachmentIDs"]);

        if (count($attachmentIDs) > 10) {
            throw new ClientException("Can't refresh more than 10 attachments");
        }

        $attachmentRows = $this->attachmentModel->getWhere(["AttachmentID" => $attachmentIDs])->resultArray();
        $refreshed = $this->attachmentService->refreshAttachments($attachmentRows);
        $results = $this->attachmentService->normalizeAttachments($refreshed);

        $out = $this->schema([":a" => $this->attachmentModel->getAttachmentSchema()], "out");
        $out->validate($results);
        return new Data($results);
    }

    /**
     * Get the provider for a source.
     *
     * @param string $typeName
     * @return AttachmentProviderInterface|null
     */
    private function getProvider(string $typeName): ?AttachmentProviderInterface
    {
        foreach ($this->attachmentService->getAllProviders() as $provider) {
            if ($provider->getTypeName() == $typeName) {
                return $provider;
            }
        }
        return null;
    }

    /**
     * Extract metadata from an array of attachment data.
     *
     * @param array $data
     * @return array
     */
    public function extractMetadata(array $data): array
    {
        $metadata = [];
        foreach ($data["metadata"] as $item) {
            $metadata[$item["labelCode"]] = $item["value"];
        }
        return $metadata;
    }

    /**
     * Normalize a single attachment for output.
     *
     * @param array $attachment
     * @return array
     */
    private function normalizeSingleAttachment(array $attachment): array
    {
        $attachmentArray = [$attachment];
        $attachmentArray = $this->attachmentService->normalizeAttachments($attachmentArray);
        $normalizedAttachment = $attachmentArray[0];
        $normalizedAttachment = ArrayUtils::camelCase($normalizedAttachment);
        $schemaArray = $this->attachmentModel->getAttachmentSchema()->getSchemaArray();
        $filteredAttachment = array_intersect_key($normalizedAttachment, $schemaArray["properties"]);
        return $filteredAttachment;
    }
}
