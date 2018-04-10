<?php
/**
 * Guest module.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Renders the "You should register or sign in" panel box.
 */
class GuestModule extends Gdn_Module {

    /** @var string  */
    public $MessageCode = 'GuestModule.Message';

    /** @var string  */
    public $MessageDefault = "It looks like you're new here. If you want to get involved, click one of these buttons!";

    /**
     *
     *
     * @param string $sender
     * @param bool $applicationFolder
     */
    public function __construct($sender = '', $applicationFolder = false) {
        if (!$applicationFolder) {
            $applicationFolder = 'Dashboard';
        }
        parent::__construct($sender, $applicationFolder);

        $this->Visible = c('Garden.Modules.ShowGuestModule');
    }

    /**
     *
     *
     * @return string
     */
    public function assetTarget() {
        return 'Panel';
    }

    /**
     * Render.
     *
     * @return string
     */
    public function toString() {
        if (!Gdn::session()->isValid()) {
            return parent::toString();
        }

        return '';
    }
}
