<?php
/**
 * User Photo module.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Renders a user's photo (if they've uploaded one).
 */
class UserPhotoModule extends Gdn_Module {

    /**
     * @var bool Can the current user edit this user's photo?
     */
    public $CanEditPhotos;

    public function __construct() {
        parent::__construct();
        $this->_ApplicationFolder = 'dashboard';
        $this->CanEditPhotos = Gdn::session()->checkRankedPermission(c('Garden.Profile.EditPhotos', true)) || Gdn::session()->checkPermission('Garden.Users.Edit');
    }

    public function assetTarget() {
        return 'Panel';
    }

    public function toString() {
        return parent::toString();
    }
}
