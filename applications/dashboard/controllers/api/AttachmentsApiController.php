<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Controllers\Api;

use AbstractApiController;
use AttachmentModel;
use Vanilla\Dashboard\Models\ExternalIssueService;
use Garden\Utils\ArrayUtils;
use Garden\Web\Data;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Dashboard\Models\ExternalIssueProviderInterface;

/**
 * API v2 endpoints for attachments.
 */
class AttachmentsApiController extends AbstractApiController
{
    private AttachmentModel $attachmentModel;

    private ExternalIssueService $externalIssueService;

    /**
     * D.I.
     *
     * @param AttachmentModel $attachmentModel
     * @param ExternalIssueService $externalIssueService
     */
    public function __construct(AttachmentModel $attachmentModel, ExternalIssueService $externalIssueService)
    {
        $this->attachmentModel = $attachmentModel;
        $this->externalIssueService = $externalIssueService;
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
        $this->permission("staff.allow");

        $in = $this->attachmentModel->getAttachmentPostSchema();
        $in->validate($body);

        $recordType = $body["recordType"];
        $recordID = $body["recordID"];

        $provider = $this->getProvider($body["source"]);
        if (!$provider) {
            throw new NotFoundException("No provider was found for this attachment source.");
        }

        $metadata = $this->extractMetadata($body);

        $issueSchema = $provider->issuePostSchema();
        $issueSchema->validate($metadata);

        $attachment = $provider->makeNewIssue($recordType, $recordID, $body);

        $attachment = $this->normalizeSingleAttachment($attachment);

        $out = $provider->fullIssueSchema();
        $out->validate($attachment, true);

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
        $this->permission("staff.allow");

        $in = $this->schema([
            "recordType:s" => ["enum" => ["discussion", "comment", "user"]],
            "recordID:i",
        ]);

        $in->validate($query);

        $foreignID = $this->attachmentModel->createForeignID($query["recordType"], $query["recordID"]);

        $attachments = $this->attachmentModel->getWhere(["ForeignID" => $foreignID])->resultArray();
        $this->normalizeAttachments($attachments);

        $out = $this->schema([":a" => $this->attachmentModel->getAttachmentSchema()], "out");
        $out->validate($attachments);

        return new Data($attachments);
    }

    /**
     * Get the provider for a source.
     *
     * @param string $sourceName
     * @return ExternalIssueProviderInterface|null
     */
    private function getProvider(string $sourceName): ?ExternalIssueProviderInterface
    {
        foreach ($this->externalIssueService->getAllProviders() as $provider) {
            if ($provider->getSourceName() == $sourceName) {
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
        $this->attachmentModel->normalizeAttachments($attachmentArray);
        $normalizedAttachment = $attachmentArray[0];
        $normalizedAttachment = ArrayUtils::camelCase($normalizedAttachment);
        $schemaArray = $this->attachmentModel->getAttachmentSchema()->getSchemaArray();
        $filteredAttachment = array_intersect_key($normalizedAttachment, $schemaArray["properties"]);
        return $filteredAttachment;
    }

    /**
     * Normalize an array of attachments for output.
     *
     * @param array $attachments
     * @return void
     */
    private function normalizeAttachments(array &$attachments): void
    {
        $this->attachmentModel->normalizeAttachments($attachments);
        $schemaArray = $this->attachmentModel->getAttachmentSchema()->getSchemaArray();
        $attachments = ArrayUtils::camelCase($attachments);
        foreach ($attachments as &$attachment) {
            $attachment = array_intersect_key($attachment, $schemaArray["properties"]);
        }
    }
}
