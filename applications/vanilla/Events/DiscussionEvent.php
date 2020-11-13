<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Community\Events;

use Garden\Events\ResourceEvent;
use Psr\Log\LogLevel;
use Vanilla\Logging\LogEntry;
use Vanilla\Logging\LoggableEventInterface;
use Vanilla\Logging\LoggerUtils;

/**
 * Represent a discussion resource event.
 */
class DiscussionEvent extends ResourceEvent implements LoggableEventInterface {

    /**
     * DiscussionEvent constructor.
     *
     * @param string $action
     * @param array $payload
     * @param array|null $sender
     */
    public function __construct(string $action, array $payload, ?array $sender = null) {
        parent::__construct($action, $payload, $sender);
        $this->addApiParams(['expand' => ['tagIDs', 'crawl']]);
    }
    /**
     * @inheritDoc
     */
    public function getLogEntry(): LogEntry {
        $context = LoggerUtils::resourceEventLogContext($this);
        $context['discussion'] = array_intersect_key($this->payload["discussion"] ?? [], [
            "discussionID" => true,
            "dateInserted" => true,
            "dateUpdated" => true,
            "updateUserID" => true,
            "insertUserID" => true,
            "url" => true,
            "name" => true,
        ]);

        $log = new LogEntry(
            LogLevel::INFO,
            LoggerUtils::resourceEventLogMessage($this),
            $context
        );

        return $log;
    }

    /**
     * @inheritdoc
     */
    public function getApiUrl() {
        [$recordType, $recordID] = $this->getRecordTypeAndID();
        return "/api/v2/discussions?discussionID={$recordID}";
    }
}
