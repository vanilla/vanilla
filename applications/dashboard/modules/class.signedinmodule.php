<?php
/**
 * SignedIn module.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Class SignedInModule.
 */
class SignedInModule extends Gdn_Module {

    public function assetTarget() {
        $this->_ApplicationFolder = 'dashboard';
        return 'Panel';
    }
}
