<?php
/**
 * User Photo module.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Renders a user's photo (if they've uploaded one).
 */
class UserPhotoModule extends Gdn_Module {

    public function __construct() {
        parent::__construct();
        $this->_ApplicationFolder = 'dashboard';
    }

    public function assetTarget() {
        return 'Panel';
    }

    public function toString() {
        $this->CanEditPhotos = c('Garden.Profile.EditPhotos');
        return parent::ToString();
    }
}
