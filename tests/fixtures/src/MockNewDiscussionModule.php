<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures;

use NewDiscussionModule;

/**
 * A mock new discussion module to test NewDiscussionModule methods.
 */
class MockNewDiscussionModule extends NewDiscussionModule {

    /**
     * Override parent constructor to avoid call to Vanilla Configuration.
     */
    public function __construct() {
        $this->DefaultButton = false;
    }
}
