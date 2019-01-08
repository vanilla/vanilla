<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts\Web;

/**
 * An interface for a view handler for Gdn_Controller.
 */
interface LegacyViewHandlerInterface {
    /**
     * Render the given view.
     *
     * @param string $path The path to the view's file.
     * @param \Gdn_Controller $controller The controller that is rendering the view.
     */
    public function render($path, $controller);
}
