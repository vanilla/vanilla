<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla;


use Garden\EventManager;

class VanillaClassLocator extends ClassLocator {
    /**
     * @var EventManager
     */
    private $eventManager;

    public function __construct(EventManager $eventManager) {
        $this->eventManager = $eventManager;
    }

    public function findMethod($object, $method) {
        $class = get_class($object);
        $event = "{$class}_{$method}_method";

        // Check for an overriding event.
        if ($this->eventManager->hasHandler($event)) {
            $handlers = $this->eventManager->getHandlers($event);
            return reset($handlers);
        } else {
            return parent::findMethod($object, $method);
        }
    }
}
