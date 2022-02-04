<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout;

use Garden\Web\Exception\NotFoundException;
use Vanilla\Web\PageHeadInterface;
use Vanilla\Web\ThemedPage;

/**
 * Base page for rendering custom layouts.
 */
class LayoutPage extends ThemedPage {

    /**
     * In the future this will be responsible for pre-hydrating layout specs.
     *
     * @inheritdoc
     */
    public function initialize() {
        // Do nothing.
    }

    /**
     * @return string
     */
    public function getAssetSection(): string {
        return "layouts";
    }
}
