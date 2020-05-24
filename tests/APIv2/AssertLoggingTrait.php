<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Vanilla\Events\EventAction;

/**
 * Use this trait if your resource is supposed to log its events.
 */
trait AssertLoggingTrait {
    protected $resourceName = 'n/a';

    /**
     * @inheritDoc
     */
    public function testDelete() {
        parent::testDelete();
        $this->assertLog(['event' => EventAction::eventName($this->resourceName, EventAction::DELETE)]);
    }

    /**
     * @inheritDoc
     */
    public function testPatchFull() {
        parent::testPatchFull();
        $this->assertLog(['event' => EventAction::eventName($this->resourceName, EventAction::UPDATE)]);
    }
}
