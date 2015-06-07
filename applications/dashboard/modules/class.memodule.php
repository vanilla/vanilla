<?php
/**
 * Me module.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
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
