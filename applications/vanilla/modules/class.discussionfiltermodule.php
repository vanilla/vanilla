<?php
/**
 * Discussion filters module
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Vanilla
 * @since 2.0
 */

/**
 * Renders the discussion filter menu.
 */
class DiscussionFilterModule extends Gdn_Module {

    public function __construct($Sender) {
        parent::__construct($Sender, 'Vanilla');
    }

    public function assetTarget() {
        return 'Panel';
    }

    public function toString() {
        return parent::ToString();
    }
}
