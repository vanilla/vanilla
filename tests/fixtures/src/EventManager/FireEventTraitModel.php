<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures\EventManager;

use Garden\EventManager;
use Garden\EventManager\FireEventTrait;

/**
 * Class for testing the abilities of FireEventTrait.
 */
class FireEventTraitModel {

    use FireEventTrait;

    /**
     * FireEventTraitModel constructor.
     *
     * @param EventManager $eventManager
     */
    public function __construct(EventManager $eventManager) {
        $this->setEventManager($eventManager);
    }

    /**
     * Fire a test event.
     *
     * @return array
     */
    public function fireTestEvent() {
        $result = $this->fireEvent("fireEventTrait_test");
        return $result;
    }
}
