<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures;

use Gdn;
use Gdn_Controller;
use NewDiscussionModule;

/**
 * A mock new discussion module to test NewDiscussionModule methods.
 */
class MockNewDiscussionModule extends NewDiscussionModule {

    /**
     * Override parent constructor to avoid call to Vanilla Configuration.
     */
    public function __construct() {
        parent::__construct();
        $this->DefaultButton = false;
    }

    /**
     * Set the sending controller.
     *
     * @param Gdn_Controller|null $sender
     */
    public function setSender($sender) {
        $this->_Sender = $sender;
    }
}
