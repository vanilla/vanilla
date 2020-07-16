<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

use Garden\EventManager;
use Garden\Events\GenericResourceEvent;
use Garden\Events\ResourceEvent;
use OpenStack\Metric\v1\Gnocchi\Models\Resource;
use PHPUnit\Framework\MockObject\MockObject;
use VanillaTests\Fixtures\SpyingEventManager;

/**
 * Trait for asserting that events are dispatched properly.
 *
 * @method fail(string $message)
 * @method assertEquals(...$args)
 */
trait EventSpyTestTrait {

    /**
     * Setup.
     */
    public function setUpEventSpyTestTrait() {
        $this->clearDispatchedEvents();
    }

    /**
     * Clear the dispatched events.
     */
    protected function clearDispatchedEvents() {
        $this->getEventManager()->clearDispatchedEvents();
    }

    /**
     * @return SpyingEventManager
     */
    protected function getEventManager(): SpyingEventManager {
        return \Gdn::getContainer()->get(EventManager::class);
    }

    /**
     * Assert that events were dispatched.
     *
     * @param ResourceEvent[] $events
     * @param array|string[] $matchPayloadFields
     * @param bool $strictCount If set to true we are asserting these are the only events dispatched.
     */
    public function assertEventsDispatched(array $events, array $matchPayloadFields = ["*"], bool $strictCount = false) {
        if ($strictCount) {
            $expectedCount = count($events);
            $dispatchedEvents = $this->getEventManager()->getDispatchedEvents();
            $actualCount = count($dispatchedEvents);
            $this->assertEquals(
                $expectedCount,
                $actualCount,
                "Expected {$expectedCount} events to be dispatched, but $actualCount events were dispatched. Dispatched Events:\n".
                json_encode($dispatchedEvents, JSON_PRETTY_PRINT)
            );
        }
        foreach ($events as $event) {
            $this->assertEventDispatched($event, $matchPayloadFields);
        }
    }

    /**
     * Assert that an event was dispatched.
     *
     * @param ResourceEvent $event
     * @param array|string[] $matchPayloadFields
     */
    public function assertEventDispatched(ResourceEvent $event, array $matchPayloadFields = ["*"]) {
        $matchAll = in_array("*", $matchPayloadFields);
        $hasEvent = false;
        $dispatchedEvents = $this->getEventManager()->getDispatchedEvents();
        /** @var ResourceEvent $dispatchedEvent */
        foreach ($dispatchedEvents as $dispatchedEvent) {
            if (!($event instanceof ResourceEvent)) {
                continue;
            }

            if ($dispatchedEvent->getFullEventName() !== $event->getFullEventName()) {
                continue;
            }

            if ($matchAll) {
                $matchPayloadFields = array_keys($event->getPayload()[$event->getType()]);
            }

            foreach ($matchPayloadFields as $matchPayloadField) {
                $dispatchedField = $dispatchedEvent->getPayload()[$dispatchedEvent->getType()][$matchPayloadField] ?? null;
                $expectedField = $event->getPayload()[$event->getType()][$matchPayloadField] ?? null;
                if ($dispatchedField !== $expectedField) {
                    continue 2;
                }
            }

            $hasEvent = true;
        }

        $this->assertTrue(
            $hasEvent,
            "Could not find a matching event for $event in dispatched events:\n" . json_encode($dispatchedEvents, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Generate an excepected event.
     *
     * @param string $type
     * @param string $action
     * @param array $payload
     * @return ResourceEvent
     */
    private function expectedResourceEvent(string $type, string $action, array $payload): ResourceEvent {
        return new GenericResourceEvent($type, $action, [
            $type => $payload,
        ], $this->getCurrentUser());
    }

    /**
     * Get the current user.
     */
    private function getCurrentUser() {
        return \Gdn::userModel()->currentFragment();
    }
}
