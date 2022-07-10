<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

/**
 * Page class that only require a title. Useful for when the page doesn't need SEO and content is rendered in JS.
 */
class SimpleTitlePage extends ThemedPage {

    /**
     * @return string
     */
    public function getAssetSection(): string {
        return "forum";
    }

    /**
     * @inheritdoc
     */
    public function initialize(string $title = "") {
        $this->setSeoRequired(false);
        $this->setSeoTitle($title);
    }
}
