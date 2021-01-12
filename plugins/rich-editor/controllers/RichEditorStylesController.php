<?php
/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

class RichEditorStylesController extends VanillaController {
    /**
     * Renders the rich text editor.
     */
    public function index() {
        $this->render();
    }

    /**
     * Renders embeds.
     */
    public function embeds() {
        $this->render();
    }

    /**
     * Renders common rich text formatting.
     */
    public function formatting() {
        $this->render();
    }

    /**
     * Renders images with various size and placement options.
     */
    public function images() {
        $this->render();
    }
}
