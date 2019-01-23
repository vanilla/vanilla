<?php
/**
 * ActivityFilter module.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Dashboard
 * @since 2.1
 */

/**
 * Renders the activity filter menu.
 */
class ActivityFilterModule extends Gdn_Module {

    /**
     *
     *
     * @return string
     */
    public function assetTarget() {
        return 'Panel';
    }

    /**
     *
     *
     * @return string
     */
    public function toString() {
        return parent::toString();
    }
}
