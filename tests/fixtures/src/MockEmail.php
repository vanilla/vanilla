<?php
/**
 * @author Dani M. <dani.m@vanillaforums.com>
 * @author Isis Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures;

/**
 * Mock email object.
 */
class MockEmail extends \Gdn_Email {

    /**
     * Always returns true because we don't want tests sending emails.
     *
     * @param string $eventName
     * @return bool
     */
    public function send($eventName = '') {
        return true;
    }
}
