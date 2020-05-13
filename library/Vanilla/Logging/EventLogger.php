<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Logging;

use Garden\Events\ResourceEvent;
use Gdn_Request;
use Gdn_Session;
use Psr\Log\LoggerInterface;

/**
 * Provides easy logging of events.
 */
class EventLogger {

    /** @var LoggerInterface */
    private $logger;

    /** @var Gdn_Request */
    private $request;

    /** @var array */
    private $actions = [ResourceEvent::ACTION_DELETE, ResourceEvent::ACTION_UPDATE];

    /** @var array */
    private $currentUser;

    /** @var array */
    private $eventActions = [];

    /**
     * Setup the event logger.
     *
     * @param LoggerInterface $logger
     * @param Gdn_Session $session
     * @param Gdn_Request $request
     */
    public function __construct(LoggerInterface $logger, Gdn_Session $session, Gdn_Request $request) {
        $this->logger = $logger;
        $this->request = $request;
        $this->currentUser = $session->User instanceof \stdClass ? (array)$session->User : [];
    }

    /**
     * Add a generic event action to log.
     *
     * @param string $action
     * @return bool
     */
    public function addAction(string $action): bool {
        if (in_array($action, $this->actions)) {
            return false;
        }
        $this->actions[] = $action;
        return true;
    }

    /**
     * Get context defaults for any events.
     *
     * @return array
     */
    private function defaultContext(): array {
        $result = [
            "domain" => $this->request->urlDomain(true),
            "ip" => $this->request->getIP(),
            "method" => $this->request->getMethod(),
            "path" => $this->request->getPath(),
            "userID" => $this->currentUser["UserID"] ?? null,
            "username" => $this->currentUser["Name"] ?? "anonymous",
        ];
        return $result;
    }

    /**
     * Log resource events that are loggable.
     *
     * @param ResourceEvent $event
     * @return ResourceEvent
     */
    public function logResourceEvent(ResourceEvent $event): ResourceEvent {
        if ($event instanceof LoggableEventInterface) {
            $entry = $event->getLogEntry();

            if ($entry instanceof LogEntry && $this->shouldLogEvent($event)) {
                $context = $entry->getContext() + $this->defaultContext();
                $this->logger->log(
                    $entry->getLevel(),
                    $entry->getMessage(),
                    $context
                );
            }
        }

        return $event;
    }

    /**
     * Add event-specific overrides for actions.
     *
     * @param string $class
     * @param string $action
     * @param bool $log
     */
    public function overrideEventAction(string $class, string $action, bool $log): void {
        $class = strtolower($class);
        if (!array_key_exists($class, $this->eventActions)) {
            $this->eventActions[$class] = [];
        }
        $this->eventActions[$class][$action] = $log;
    }

    /**
     * Should the event be logged?
     *
     * @param ResourceEvent $event
     * @return bool
     */
    private function shouldLogEvent(ResourceEvent $event): bool {
        $class = strtolower(get_class($event));
        $action = $event->getAction();

        $eventRule = $this->eventActions[$class][$action] ?? null;
        if ($eventRule === null) {
            $result = in_array($action, $this->actions);
        } else {
            $result = $eventRule;
        }

        return $result;
    }

    /**
     * Remove a generic event action to log.
     *
     * @param string $action
     * @return bool
     */
    public function removeAction(string $action): bool {
        if (($index = array_search($action, $this->actions)) === false) {
            return false;
        }
        unset($this->actions[$index]);
        return true;
    }
}
