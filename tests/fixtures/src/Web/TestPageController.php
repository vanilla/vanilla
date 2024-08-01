<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures\Web;

use Vanilla\Web\PageDispatchController;

/**
 * Test page controller fixture.
 */
class TestPageController extends PageDispatchController
{
    /**
     * @return \Garden\Web\Data
     */
    public function get_hello()
    {
        return $this->useSimplePage("Hello")->render();
    }

    /**
     * @return \Garden\Web\Data
     */
    public function get_world()
    {
        return $this->useSimplePage("World")->render();
    }
}
