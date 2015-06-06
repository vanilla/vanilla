<?php
/**
 * Conversations controller.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Conversations
 * @since 2.0
 */

/**
 * Master controller for Conversations for others to extend.
 */
class ConversationsController extends Gdn_Controller {
    /**
     * Returns an array of pages that contain settings information for this application.
     *
     * @return array
     */
    public function GetSettingsPages(&$Menu) {
        // There are no configuration pages for Conversations
    }

    /**
     * Do-nothing construct to let children constructs bubble up.
     *
     * @access public
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Include JS, CSS, and modules used by all methods.
     *
     * Always called by dispatcher before controller's requested method.
     *
     * @since 2.0.0
     * @access public
     */
    public function Initialize() {
        // You've got to be signed in to send private messages.
        if (!Gdn::Session()->IsValid()) {
            Redirect('/entry/signin?Target='.urlencode($this->SelfUrl));
        }

        if ($this->DeliveryType() == DELIVERY_TYPE_ALL) {
            $this->Head = new HeadModule($this);
            $this->AddJsFile('jquery.js');
            $this->AddJsFile('jquery.livequery.js');
            $this->AddJsFile('jquery.form.js');
            $this->AddJsFile('jquery.popup.js');
            $this->AddJsFile('jquery.gardenhandleajaxform.js');
            $this->AddJsFile('jquery.autosize.min.js');
            $this->AddJsFile('jquery.tokeninput.js');
            $this->AddJsFile('global.js');
            $this->AddJsFile('conversations.js');
        }

        $this->AddCssFile('style.css');
        $this->AddCssFile('vanillicon.css', 'static');
        parent::Initialize();
    }
}
