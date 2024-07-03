<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

use AttachmentModel;
use Garden\EventManager;
use Garden\Schema\Schema;
use Garden\Utils\ContextException;
use Garden\Web\Exception\ForbiddenException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Community\Events\EscalationEvent;
use Vanilla\Community\Events\TicketEscalationEvent;
use Vanilla\CurrentTimeStamp;
use Vanilla\Logging\ErrorLogger;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\DebugUtils;

/**
 * Class AttachmentService
 */
class AttachmentService
{
    private $providers = [];

    private AttachmentModel $attachmentModel;
    private \CommentModel $commentModel;
    private \DiscussionModel $discussionModel;
    private EventManager $eventManager;

    /**
     * DI.
     */
    public function __construct(
        AttachmentModel $attachmentModel,
        \CommentModel $commentModel,
        \DiscussionModel $discussionModel,
        EventManager $eventManager
    ) {
        $this->attachmentModel = $attachmentModel;
        $this->commentModel = $commentModel;
        $this->discussionModel = $discussionModel;
        $this->eventManager = $eventManager;
    }

    /**
     * Add a provider to the service.
     *
     * @param AttachmentProviderInterface $provider
     * @return void
     */
    public function addProvider(AttachmentProviderInterface $provider)
    {
        $this->providers[$provider->getTypeName()] = $provider;
    }

    /**
     * Get all the providers in the service.
     *
     * @return AttachmentProviderInterface[]
     */
    public function getAllProviders(): array
    {
        return $this->providers;
    }

    /**
     * Get a provider by source name.
     *
     * @param string $typeName
     *
     * @return AttachmentProviderInterface
     */
    public function getProvider(string $typeName): ?AttachmentProviderInterface
    {
        return $this->providers[$typeName] ?? null;
    }

    /**
     * Get a catalog of all the providers in the service.
     *
     * @return array
     */
    public function getCatalog()
    {
        $catalog = [];
        foreach ($this->getAllProviders() as $provider) {
            if ($provider->hasReadPermissions() || $provider->hasWritePermissions()) {
                $catalogEntry = [];

                $catalogEntry = array_merge($catalogEntry, [
                    "attachmentType" => $provider->getTypeName(),
                    "title" => $provider->getTitleLabelCode(),
                    "externalIDLabel" => $provider->getExternalIDLabelCode(),
                    "logoIcon" => $provider->getLogoIconName(),
                ]);

                if ($provider->hasWritePermissions()) {
                    $catalogEntry = array_merge($catalogEntry, [
                        "name" => $provider->getProviderName(),
                        "label" => $provider->getCreateLabelCode(),
                        "submitButton" => $provider->getSubmitLabelCode(),
                        "recordTypes" => $provider->getRecordTypes(),
                    ]);
                }

                $catalogEntry = array_merge($catalogEntry, [
                    "canEscalateOwnContent" => $provider->canEscalateOwnContent(),
                    "escalationDelayUnit" => $provider->getEscalationDelayUnit(),
                    "escalationDelayLength" => $provider->getEscalationDelayLength(),
                ]);

                $catalog[$provider->getTypeName()] = $catalogEntry;
            }
        }
        return $catalog;
    }

    /**
     * Given a list of attachment rows, refresh them.
     *
     * @param array $attachmentRows
     * @return array
     */
    public function refreshAttachments(array $attachmentRows): array
    {
        $resultRowsByID = [];
        $attachmentIDsOrdered = array_column($attachmentRows, "AttachmentID");
        $attachmentsByType = ArrayUtils::arrayColumnArrays($attachmentRows, null, "Type");

        foreach ($attachmentsByType as $type => $attachmentRows) {
            $provider = $this->getProvider($type);
            if (!$provider) {
                continue;
            }
            $refreshedRows = $provider->refreshAttachments($attachmentRows);
            foreach ($refreshedRows as $refreshedRow) {
                $resultRowsByID[$refreshedRow["AttachmentID"]] = $refreshedRow;
            }
        }

        $resultsOrdered = [];
        foreach ($attachmentIDsOrdered as $attachmentID) {
            if (isset($resultRowsByID[$attachmentID])) {
                $resultsOrdered[] = $resultRowsByID[$attachmentID];
            }
        }
        return $resultsOrdered;
    }

    /**
     * Refresh stale attachments.
     *
     * @param array $attachmentRows
     * @return array
     */
    public function refreshStale(array $attachmentRows): array
    {
        $staleAttachments = array_filter($attachmentRows, function ($attachment) {
            $provider = $this->getProvider($attachment["Type"]);
            if (!$provider) {
                return false;
            }
            $dateUpdated = new \DateTime($attachment["DateUpdated"] ?? $attachment["DateInserted"]);
            $refreshSeconds = $provider->getRefreshTimeSeconds();

            $cutoffDate = CurrentTimeStamp::getDateTime()->modify("-{$refreshSeconds} seconds");
            if ($dateUpdated->getTimestamp() > $cutoffDate->getTimestamp()) {
                return false;
            }
            return true;
        });

        $attachmentsByID = array_column($attachmentRows, null, "AttachmentID");
        $refreshed = $this->refreshAttachments($staleAttachments);
        $refreshedByID = array_column($refreshed, null, "AttachmentID");

        $results = [];
        foreach ($attachmentsByID as $attachmentID => $attachment) {
            $results[] = $refreshedByID[$attachmentID] ?? $attachment;
        }
        return $results;
    }

    /**
     * Given a list of attachment rows, normalize them, filter them to known providers, and validate them.
     *
     * @param array $attachments
     * @return array
     */
    public function normalizeAttachments(array $attachments): array
    {
        $normalizedAttachments = [];
        $schema = Schema::parse([":a" => \AttachmentModel::getAttachmentSchema()]);
        foreach ($attachments as $attachment) {
            $type = $attachment["Type"];
            $provider = $this->getProvider($type);
            if (!$provider) {
                continue;
            }

            if (!($provider->canViewBasicAttachment($attachment) || $provider->canViewFullAttachment($attachment))) {
                // Current user doesn't have access.
                continue;
            }

            // Some standard normalization
            $normalized = $attachment;
            $normalized["attachmentType"] = $provider->getTypeName();
            $normalized["sourceUrl"] = $normalized["SourceURL"];
            $normalized = array_merge($normalized, \AttachmentModel::splitForeignID($normalized["ForeignID"]));

            try {
                $normalized = $provider->normalizeAttachment($normalized);
                $normalized["DateUpdated"] = $normalized["DateUpdated"] ?? $normalized["DateInserted"];
                $normalized["LastModifiedDate"] = $normalized["LastModifiedDate"] ?? $normalized["DateInserted"];

                if ($lastModifiedDate = $normalized["LastModifiedDate"] ?? null) {
                    $normalized["metadata"][] = [
                        "labelCode" => "Last Modified",
                        "value" => (new \DateTime($lastModifiedDate))->format(\DateTime::RFC3339_EXTENDED),
                        "format" => "date-time",
                    ];
                }

                $normalized = ArrayUtils::camelCase($normalized);
                $normalized["status"] = $normalized["status"] ?? "unknown";
                $normalizedAttachments[] = $normalized;
            } catch (\Throwable $ex) {
                if (DebugUtils::isTestMode()) {
                    throw $ex;
                } else {
                    ErrorLogger::warning($ex, ["attachment", $attachment["Type"]]);
                }
            }
        }

        $result = $schema->validate($normalizedAttachments);
        return $result;
    }

    /**
     * Get a record that we can attatch to.
     *
     * @param string $recordType
     * @param string $recordID
     * @return array{name: string, url: string, body: string, insertUserID: int, dateInserted: string}
     */
    public function getRecordToAttachTo(string $recordType, string $recordID): array
    {
        switch ($recordType) {
            case "discussion":
                $record = $this->discussionModel->getID($recordID, DATASET_TYPE_ARRAY);

                if (!$record) {
                    throw new NotFoundException("Record not found");
                }
                $record = $this->discussionModel->normalizeRow($record);
                break;
            case "comment":
                $record = $this->commentModel->getID($recordID, DATASET_TYPE_ARRAY);

                if (!$record) {
                    throw new NotFoundException("Record not found");
                }

                $record = $this->commentModel->normalizeRow($record);
                break;
            default:
                throw new ContextException("Invalid record type.", 400, [
                    "recordType" => $recordType,
                    "recordID" => $recordID,
                ]);
        }
        return $record;
    }

    /**
     * Create an attachment.
     *
     * @param array $body
     * @return array
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    public function createAttachment(array $body)
    {
        $attachmentType = $body["attachmentType"] ?? "";
        $provider = $this->getProvider($attachmentType);
        if (!$provider) {
            throw new NotFoundException("No provider was found for this attachment type.");
        }
        $basicBody = $this->attachmentModel->getAttachmentPostSchema()->validate($body);
        $recordType = $basicBody["recordType"];
        $recordID = $basicBody["recordID"];

        if (!$provider->canCreateAttachmentForRecord($recordType, $recordID)) {
            throw new ForbiddenException("You do not have permission to create attachments using this provider.");
        }

        $fullSchema = $this->attachmentModel
            ->getAttachmentPostSchema()
            ->merge($provider->getHydratedFormSchema($recordType, $recordID, $body));

        $body = $fullSchema->validate($body);

        $attachment = $provider->createAttachment($recordType, $recordID, $body);
        // Dispatch a Discussion event (close)
        if ($recordType === "discussion") {
            $discussion = $this->discussionModel->normalizeRow(
                $this->discussionModel->getID($recordID, DATASET_TYPE_ARRAY)
            );
            $trackingEventInterface = EscalationEvent::fromDiscussion(
                EscalationEvent::POST_COLLECTION_NAME,
                [
                    "recordType" => $recordType,
                    "isEscalation" => $provider->getIsEscalation(),
                    "attachment" => $attachment,
                ],
                $discussion
            );
        } elseif ($recordType === "comment") {
            $comment = $this->commentModel->normalizeRow($this->commentModel->getID($recordID, DATASET_TYPE_ARRAY));
            $trackingEventInterface = EscalationEvent::fromComment(
                EscalationEvent::POST_COLLECTION_NAME,
                [
                    "recordType" => $recordType,
                    "isEscalation" => $provider->getIsEscalation(),
                    "attachment" => $attachment,
                ],
                $comment
            );
        }
        $this->eventManager->dispatch($trackingEventInterface);
        return $attachment;
    }
}
