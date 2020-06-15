<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Logging;

use Garden\Events\ResourceEvent;
use Psr\Log\LoggerInterface;

/**
 * Provides easy logging of events.
 */
class ResourceEventLogger {

    /** @var array */
    private $exclude = [];

    /** @var array */
    private $include = [
        ["*", ResourceEvent::ACTION_DELETE],
        ["*", ResourceEvent::ACTION_UPDATE],
    ];

    /** @var LoggerInterface */
    private $logger;

    /**
     * Setup the event logger.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    /**
     * Log resource events that are loggable.
     *
     * @param LoggableEventInterface $event
     * @return ResourceEvent
     */
    public function logResourceEvent(LoggableEventInterface $event): ResourceEvent {
        try {
            $entry = $event->getLogEntry();

            if ($event instanceof ResourceEvent && $this->shouldLogEvent($event)) {
                $this->logger->log(
                    $entry->getLevel(),
                    $entry->getMessage(),
                    $entry->getContext()
                );
            }

            return $event;
        } catch (\Exception $ex) {
            trigger_error($ex->getMessage(), E_USER_WARNING);
        }
    }

    /**
     * Add logging for a specific action of a specific resource. Supports patterns for matching.
     *
     * @param string $class
     * @param string $action
     * @return bool
     */
    public function includeAction(string $class, string $action): bool {
        foreach ($this->include as [$includeClass, $includeAction]) {
            if ($class === $includeClass && $action === $includeAction) {
                return false;
            }
        }

        $this->include[] = [$class, $action];
        return true;
    }

    /**
     * Avoid logging for a specific action of a specific resource. Supports patterns for matching.
     *
     * @param string $class
     * @param string $action
     * @return bool
     */
    public function excludeAction(string $class, string $action): bool {
        foreach ($this->exclude as [$excludeClass, $excludeAction]) {
            if ($class === $excludeClass && $action === $excludeAction) {
                return false;
            }
        }

        $this->exclude[] = [$class, $action];
        return true;
    }

    /**
     * Should the event be logged?
     *
     * @param ResourceEvent $event
     * @return bool
     */
    private function shouldLogEvent(ResourceEvent $event): bool {
        $class = get_class($event);
        $action = $event->getAction();

        foreach ($this->exclude as [$excludeClass, $excludeAction]) {
            if (fnmatch($excludeClass, $class, FNM_NOESCAPE) && fnmatch($excludeAction, $action, FNM_NOESCAPE)) {
                return false;
            }
        }

        foreach ($this->include as [$includeClass, $includeAction]) {
            if (fnmatch($includeClass, $class, FNM_NOESCAPE) && fnmatch($includeAction, $action, FNM_NOESCAPE)) {
                return true;
            }
        }

        return false;
    }
}
