<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures;

use Garden\EventManager;

/**
 * An event manager that tracks all dispatched events so can make assertions on them.
 */
class SpyingEventManager extends EventManager {

    /** @var array */
    private $firedEvents = [];

    /** @var object[] */
    private $dispatchedEvents;

    /**
     * Overridden to track dispatched events.
     *
     * @inheritdoc
     */
    public function dispatch(object $event) {
        $this->dispatchedEvents[] = $event;
        return parent::dispatch($event);
    }

    /**
     * Fire an event.
     *
     * @param string $event The name of the event.
     * @param mixed $args Any arguments to pass along to the event handlers.
     * @return array Returns the result of the event handlers where each handler's result is an item in the array.
     */
    public function fire($event, ...$args) {
        $this->firedEvents[] = [$event, $args];
        return parent::fire($event, ...$args);
    }

    /**
     * Clear the dispatched events.
     */
    public function clearDispatchedEvents() {
        $this->dispatchedEvents = [];
    }

    /**
     * Clear fired events.
     */
    public function clearFiredEvents(): void {
        $this->firedEvents = [];
    }

    /**
     * Get fired events since the last reset.
     *
     * @return array
     */
    public function getFiredEvents(): array {
        return $this->firedEvents;
    }

    /**
     * @return object[]
     */
    public function getDispatchedEvents(): array {
        return $this->dispatchedEvents;
    }
}
