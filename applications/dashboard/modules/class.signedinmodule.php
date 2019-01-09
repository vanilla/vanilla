<?php
/**
 * SignedIn module.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
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
