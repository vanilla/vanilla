<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Analytics;

use Garden\EventManager;
use Garden\Web\Exception\NotFoundException;

/**
 * Inspects requests payloads and dispatches events if a corresponding event provider is found
 */
class EventProviderService
{
    /** @var EventProviderInterface[] */
    private $eventProviders = [];

    /** @var EventManager */
    private $eventManager;

    /**
     * Constructor
     *
     * @param EventManager $eventManager
     */
    public function __construct(EventManager $eventManager)
    {
        $this->eventManager = $eventManager;
    }

    /**
     * @param EventProviderInterface $eventProvider
     * @return void
     */
    public function registerEventProvider(EventProviderInterface $eventProvider)
    {
        $this->eventProviders[] = $eventProvider;
    }

    /**
     * Loops through registered event providers until a valid provider is found and is then used to generate
     * the event object which is dispatched by the event manager.
     *
     * @return void
     */
    public function handleRequest(array $body)
    {
        foreach ($this->eventProviders as $eventProvider) {
            if ($eventProvider->canHandleRequest($body)) {
                $event = $eventProvider->getEvent($body);
                $this->eventManager->dispatch($event);
                break;
            }
        }
    }
}
