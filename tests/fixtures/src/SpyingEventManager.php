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
     * Clear the dispatched events.
     */
    public function clearDispatchedEvents() {
        $this->dispatchedEvents = [];
    }

    /**
     * @return object[]
     */
    public function getDispatchedEvents(): array {
        return $this->dispatchedEvents;
    }
}
