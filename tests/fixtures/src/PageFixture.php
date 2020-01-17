<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures;

use Vanilla\Web\Page;

/**
 * Fixture for testing the page class.
 */
class PageFixture extends Page {

    /**
     * @inheritdoc
     */
    public function getAssetSection(): string {
        return 'tests';
    }

    /**
     * @inheritdoc
     */
    public function initialize() {
        return;
    }
}
