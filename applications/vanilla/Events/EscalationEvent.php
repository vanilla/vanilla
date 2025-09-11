<?php
/**
 * @author Pavel Goncharopv <pgoncharov@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Community\Events;

use Garden\Events\ResourceEvent;
use Garden\Events\TrackingEventInterface;
use Psr\Log\LogLevel;
use Vanilla\Analytics\TrackableCommunityModel;
use Vanilla\Dashboard\Models\UserFragment;
use Vanilla\Logging\LogEntry;
use Vanilla\Logging\LoggableEventInterface;
use Vanilla\Logging\LoggerUtils;

/**
 * Represents a page view event
 */
class EscalationEvent extends ResourceEvent implements LoggableEventInterface, TrackingEventInterface
{
    const COLLECTION_NAME = "escalation";

    const POST_COLLECTION_NAME = "escalatePost";
    const ARTICLE_COLLECTION_NAME = "escalateArticle";

    /**
     * Constructor
     *
     * @param string $action
     * @param array $payload
     * @param UserFragment $sender
     */
    private function __construct(string $action, array $payload, UserFragment $sender)
    {
        parent::__construct($action, $payload, $sender);
    }

    /**
     * @param string $action
     * @param array $attachment
     * @param array $discussion
     * @return static
     */
    public static function fromDiscussion(string $action, array $attachment, array $discussion): self
    {
        $discussion["body"] = null;
        $attachment["discussion"] = $discussion;
        return new EscalationEvent($action, $attachment, \Gdn::userModel()->currentFragment());
    }

    /**
     * Get the discussion data.
     *
     * @return array
     */
    public function getDiscussion(): array
    {
        return $this->payload["discussion"];
    }

    public static function fromComment(string $action, array $attachment, array $comment): self
    {
        $comment["body"] = null;
        $attachment["comment"] = $comment;
        return new EscalationEvent($action, $attachment, \Gdn::userModel()->currentFragment());
    }

    /**
     * Get the comment data.
     *
     * @return array
     */
    public function getComment(): array
    {
        return $this->payload["comment"];
    }

    /**
     * Get the record type.
     *
     * @return string
     */
    public function getRecordType(): string
    {
        return $this->payload["recordType"];
    }

    /**
     * Get the attachment type.
     *
     * @return string
     */
    public function getAttachmentType(): string
    {
        return $this->payload["attachment"]["Type"];
    }

    /**
     * @inheritdoc
     */
    public function getTrackableCollection(): ?string
    {
        return self::COLLECTION_NAME;
    }

    /**
     * @inheritdoc
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @inheritdoc
     */
    public function getPayload(): ?array
    {
        return $this->payload;
    }

    public function getTrackableAttachment()
    {
        $attachment = $this->payload["attachment"];
        return [
            "attachmentID" => $attachment["AttachmentID"],
            "attachmentType" => $attachment["Type"],
            "isEscalation" => $this->payload["isEscalation"],
            "sourceID" => $attachment["SourceID"],
            "sourceUrl" => $attachment["SourceURL"],
            "source" => $attachment["Source"],
        ];
    }

    /**
     * Get event data needed for tracking.
     *
     * @param TrackableCommunityModel $trackableCommunity
     *
     * @return array
     */
    public function getTrackablePayload(TrackableCommunityModel $trackableCommunity): array
    {
        $trackingData = $this->getTrackableAttachment();
        $this->payload["attachment"] = null;

        if ($this->getRecordType() === "discussion") {
            if (isset($this->getDiscussion()["siteSectionIDs"])) {
                $trackingData["siteSectionID"] = $this->getDiscussion()["siteSectionIDs"][0];
            }
            $trackingData = array_merge(
                $trackingData,
                $trackableCommunity->getTrackableDiscussion($this->getDiscussion())
            );
            $trackingData["discussionName"] = $trackingData["name"];
            $this->payload["discussion"] = null;
        } elseif ($this->getRecordType() === "comment") {
            if (isset($this->getComment()["siteSectionIDs"])) {
                $trackingData["siteSectionID"] = $this->getDiscussion()["siteSectionIDs"][0];
            }
            $trackingData = array_merge($trackingData, $trackableCommunity->getTrackableComment($this->getComment()));
            $trackingData["recordType"] = $this->getRecordType();

            $this->payload["comment"] = null;
        }

        // If the siteSectionID is set, we add it to the payload. We only send the first canonical one to keen.

        return $trackingData;
    }

    /**
     * @inheritdoc
     */
    public function getSiteSectionID(): ?string
    {
        return $this->payload["ticketEscalation"]["siteSectionID"] ?? null;
    }

    public function getLogEntry(): LogEntry
    {
        $context = LoggerUtils::resourceEventLogContext($this);
        $message = "User escalated ";
        if ($this->getRecordType() === "discussion") {
            $context["discussion"] = array_intersect_key($this->getDiscussion() ?? [], [
                "discussionID" => true,
                "categoryID" => true,
                "type" => true,
                "dateInserted" => true,
                "dateUpdated" => true,
                "updateUserID" => true,
                "insertUserID" => true,
                "url" => true,
                "name" => true,
            ]);
            $message .= "discussion `{$context["discussion"]["name"]}` to ";
        } elseif ($this->getRecordType() === "comment") {
            $context["comment"] = array_intersect_key($this->getComment() ?? [], [
                "commentID" => true,
                "discussionID" => true,
                "categoryID" => true,
                "type" => true,
                "dateInserted" => true,
                "dateUpdated" => true,
                "updateUserID" => true,
                "insertUserID" => true,
                "url" => true,
                "discussionName" => true,
            ]);
            $message .= "comment `RE: {$context["comment"]["discussionName"]}` to ";
        }
        $message .= $this->getAttachmentType();
        $log = new LogEntry(LogLevel::INFO, $message, $context);
        return $log;
    }
}
