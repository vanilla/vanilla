<?php
/**
 * Discussion filters module
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
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
