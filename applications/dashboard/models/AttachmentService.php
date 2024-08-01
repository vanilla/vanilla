<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

use AttachmentModel;
use CommentModel;
use DiscussionModel;
use Garden\EventManager;
use Garden\Schema\Schema;
use Garden\Utils\ContextException;
use Garden\Web\Exception\ForbiddenException;
use Garden\Web\Exception\NotFoundException;
use Gdn;
use Vanilla\Community\Events\EscalationEvent;
use Vanilla\CurrentTimeStamp;
use Vanilla\Forum\Models\CommunityManagement\EscalationModel;
use Vanilla\Forum\Models\CommunityManagement\EscalationStatusProviderInterface;
use Vanilla\Logging\ErrorLogger;
use Vanilla\Models\Model;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\DebugUtils;
use Vanilla\Web\TwigRenderTrait;

/**
 * Class AttachmentService
 */
class AttachmentService
{
    use TwigRenderTrait;

    private array $providers = [];

    /**
     * DI.
     */
    public function __construct(private AttachmentModel $attachmentModel, private EventManager $eventManager)
    {
    }

    /**
     * Not DIed to prevent infinite loops.
     *
     * @return EscalationModel
     */
    private function escalationModel(): EscalationModel
    {
        return \Gdn::getContainer()->get(EscalationModel::class);
    }

    /**
     * Add a provider to the service.
     *
     * @param AttachmentProviderInterface $provider
     * @return void
     */
    public function addProvider(AttachmentProviderInterface $provider): void
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
            if ($provider->hasReadPermissions()) {
                $catalogEntry = [];

                $catalogEntry = array_merge($catalogEntry, [
                    "writeableContentScope" => $provider->getWriteableContentScope(),
                    "attachmentType" => $provider->getTypeName(),
                    "title" => $provider->getTitleLabelCode(),
                    "externalIDLabel" => $provider->getExternalIDLabelCode(),
                    "logoIcon" => $provider->getLogoIconName(),
                    "name" => $provider->getProviderName(),
                    "label" => $provider->getCreateLabelCode(),
                    "submitButton" => $provider->getSubmitLabelCode(),
                    "recordTypes" => $provider->getRecordTypes(),
                    "escalationDelayUnit" => $provider->getEscalationDelayUnit(),
                    "escalationDelayLength" => $provider->getEscalationDelayLength(),
                ]);

                //Get if there is any additional info to be displayed in the catalog
                $catalogEntry = array_merge($catalogEntry, $provider->getAdditionalCatalogInfo());

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
                $model = $this->getDiscussionModel();
                $record = $model->getID($recordID, DATASET_TYPE_ARRAY);

                if (!$record) {
                    throw new NotFoundException("Record not found");
                }
                $record = $model->normalizeRow($record);
                break;
            case "comment":
                $model = $this->getCommentModel();
                $record = $model->getID($recordID, DATASET_TYPE_ARRAY);

                if (!$record) {
                    throw new NotFoundException("Record not found");
                }

                $record = $model->normalizeRow($record);
                break;
            case "escalation":
                $escalation =
                    $this->escalationModel()->queryEscalations(
                        ["escalationID" => $recordID],
                        options: [
                            Model::OPT_LIMIT => 1,
                        ]
                    )[0] ?? null;
                if ($escalation === null) {
                    throw new NotFoundException("Escalation", [
                        "escalationID" => $escalation,
                    ]);
                }

                $record = $escalation;

                // Construct a nice body from the escalation.
                $twig = <<<TWIG
<p><a href="{{ escalation.url }}">{{escalation.name}}</a> was escalated from a post in the <a href="{{ escalation.placeRecordUrl }}">{{escalation.placeRecordName}}</a> category.</p>
<p>It was reported {{escalation.countReports}} times for the following reasons:</p>
<ul>
{% for reason in escalation.reportReasons %}
<li><strong>{{ reason.name }}</strong> - {{ reason.description }}</li>
{% endfor %}
</ul>
<p>See <a href="{{ escalation.url }}">the escalation details</a> in Vanilla.</p>
TWIG;
                $html = $this->renderTwigFromString($twig, [
                    "escalation" => $escalation,
                ]);
                // Make sure we don't have extra newlines screwing up rich formatting.
                $html = str_replace("\n", "", $html);
                $record["body"] = $html;
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
            $model = $this->getDiscussionModel();
            $discussion = $model->normalizeRow($model->getID($recordID, DATASET_TYPE_ARRAY));
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
            $model = $this->getCommentModel();
            $comment = $model->normalizeRow($model->getID($recordID, DATASET_TYPE_ARRAY));
            $trackingEventInterface = EscalationEvent::fromComment(
                EscalationEvent::POST_COLLECTION_NAME,
                [
                    "recordType" => $recordType,
                    "isEscalation" => $provider->getIsEscalation(),
                    "attachment" => $attachment,
                ],
                $comment
            );
        } else {
            $trackingEventInterface = null;
        }
        if ($trackingEventInterface) {
            $this->eventManager->dispatch($trackingEventInterface);
        }

        if ($provider instanceof EscalationStatusProviderInterface && $recordType === "escalation") {
            $this->escalationModel()->update(
                set: [
                    "status" => $provider->getStatusID(),
                ],
                where: [
                    "escalationID" => $recordID,
                ]
            );
        }

        return $attachment;
    }

    /**
     * @return DiscussionModel
     */
    private function getDiscussionModel(): DiscussionModel
    {
        return Gdn::getContainer()->get(DiscussionModel::class);
    }

    /**
     * @return CommentModel
     */
    private function getCommentModel(): CommentModel
    {
        return Gdn::getContainer()->get(CommentModel::class);
    }
}
