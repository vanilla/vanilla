<?php
/**
 * Guest module.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
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
     * @param string $Sender
     * @param bool $ApplicationFolder
     */
    public function __construct($Sender = '', $ApplicationFolder = FALSE) {
        if (!$ApplicationFolder)
            $ApplicationFolder = 'Dashboard';
        parent::__construct($Sender, $ApplicationFolder);

        $this->Visible = C('Garden.Modules.ShowGuestModule');
    }

    /**
     *
     *
     * @return string
     */
    public function AssetTarget() {
        return 'Panel';
    }

    /**
     * Render.
     *
     * @return string
     */
    public function ToString() {
        if (!Gdn::Session()->IsValid())
            return parent::ToString();

        return '';
    }

}
