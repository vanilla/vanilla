<?php
/**
 * ActivityFilter module.
 *
 * @copyright 2008-2015 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
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
    public function AssetTarget() {
        return 'Panel';
    }

    /**
     *
     *
     * @return string
     */
    public function ToString() {
        return parent::ToString();
    }
}
