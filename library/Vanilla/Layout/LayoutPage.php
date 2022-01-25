<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout;

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
        // TODO: prehydrate layout.
    }

    /**
     * @return string
     */
    public function getAssetSection(): string {
        return "layouts";
    }
}
