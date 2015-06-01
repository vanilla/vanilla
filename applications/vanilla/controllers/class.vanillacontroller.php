<?php
/**
 * Vanilla controller
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Vanilla
 * @since 2.0
 */

/**
 * Master application controller for Vanilla, extended by all others except Settings.
 */
class VanillaController extends Gdn_Controller {

    /**
     * Include JS, CSS, and modules used by all methods.
     *
     * Always called by dispatcher before controller's requested method.
     *
     * @since 2.0.0
     * @access public
     */
    public function Initialize() {
        // Set up head
        $this->Head = new HeadModule($this);
        $this->AddJsFile('jquery.js');
        $this->AddJsFile('jquery-ui.js');
        $this->AddJsFile('jquery.livequery.js');
        $this->AddJsFile('jquery.form.js');
        $this->AddJsFile('jquery.popup.js');
        $this->AddJsFile('jquery.gardenhandleajaxform.js');
        $this->AddJsFile('jquery.atwho.js');
        $this->AddJsFile('global.js');
        $this->AddCssFile('style.css');
        $this->AddCssFile('vanillicon.css', 'static');

        // Add modules
//      $this->AddModule('MeModule');
        $this->AddModule('GuestModule');
        $this->AddModule('SignedInModule');

        parent::Initialize();
    }

}
