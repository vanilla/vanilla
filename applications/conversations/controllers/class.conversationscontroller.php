<?php
/**
 * Conversations controller.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
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
    public function getSettingsPages(&$menu) {
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
    public function initialize() {
        // You've got to be signed in to send private messages.
        if (!Gdn::session()->isValid()) {
            redirectTo('/entry/signin?Target='.urlencode($this->SelfUrl));
        }

        if ($this->deliveryType() == DELIVERY_TYPE_ALL) {
            $this->Head = new HeadModule($this);
            $this->addJsFile('jquery.js');
            $this->addJsFile('jquery.form.js');
            $this->addJsFile('jquery.popup.js');
            $this->addJsFile('jquery.popin.js');
            $this->addJsFile('jquery.gardenhandleajaxform.js');
            $this->addJsFile('jquery.autosize.min.js');
            $this->addJsFile('jquery.tokeninput.js');
            $this->addJsFile('global.js');
            $this->addJsFile('conversations.js');
        }

        $this->addCssFile('style.css');
        $this->addCssFile('vanillicon.css', 'static');
        parent::initialize();
    }
}
