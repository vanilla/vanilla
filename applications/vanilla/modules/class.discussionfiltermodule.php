<?php
/**
 * Discussion filters module
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Vanilla
 * @since 2.0
 */

/**
 * Renders the discussion filter menu.
 */
class DiscussionFilterModule extends Gdn_Module {

    public function __construct($sender) {
        parent::__construct($sender, 'Vanilla');
    }

    public function assetTarget() {
        return 'Panel';
    }

    public function toString() {
        return parent::toString();
    }
}
