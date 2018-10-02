<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Garden\EventManager;

use RuntimeException;
use Garden\EventManager;

/**
 * Trait for adding the ability to fire events with EventManager.
 */
trait FireEventTrait {

    /** @var EventManager */
    private $eventManager;

    /**
     * Fire an event.
     *
     * @param string $event The name of the event.
     * @param mixed $args Any arguments to pass along to the event handlers.
     * @return array
     */
    protected function fireEvent($event, ...$args) {
        $result = $this->getEventManager()->fire($event, ...$args);
        return $result;
    }

    /**
     * Get the configured EventManager instance.
     *
     * @return EventManager
     * @throws RuntimeException If EventManager dependency has not yet been set.
     */
    protected function getEventManager(): EventManager {
        if (!($this->eventManager instanceof EventManager)) {
            throw new RuntimeException("Instance of ".EventManager::class." not available.");
        }

        return $this->eventManager;
    }

    /**
     * Set the EventManager dependency.
     * @param EventManager $eventManager
     */
    protected function setEventManager(EventManager $eventManager) {
        $this->eventManager = $eventManager;
    }
}
