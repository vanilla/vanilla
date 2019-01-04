<?php
/**
 * Me module.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Dashboard
 * @since 2.1
 */

/**
 * Selfish bastard.
 */
class MeModule extends Gdn_Module {

    /** @var string  */
    public $CssClass = '';

    public function __construct() {
        parent::__construct();
        $this->_ApplicationFolder = 'dashboard';
    }

    public function assetTarget() {
        return 'Panel';
    }
}
