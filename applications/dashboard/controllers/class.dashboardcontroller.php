<?php
/**
 * Master application controller for Dashboard, extended by most others.
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Root class for the Dashboard's controllers.
 */
class DashboardController extends Gdn_Controller {
    /**
     * Set PageName.
     *
     * @since 2.0.0
     * @access public
     */
    public function __construct() {
        parent::__construct();
        $this->PageName = 'dashboard';
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
        $this->Head = new HeadModule($this);
        $this->addJsFile('jquery.js');
        $this->addJsFile('jquery.form.js');
        $this->addJsFile('jquery.popup.js');
        $this->addJsFile('jquery.gardenhandleajaxform.js');
        $this->addJsFile('magnific-popup.min.js');
        $this->addJsFile('jquery.autosize.min.js');
        $this->addJsFile('global.js');

        if (in_array($this->ControllerName, array('profilecontroller', 'activitycontroller'))) {
            $this->addCssFile('style.css');
            $this->addCssFile('vanillicon.css', 'static');
        } else {
            if (!c('Garden.Cdns.Disable', false)) {
                $this->addCssFile('https://fonts.googleapis.com/css?family=Rokkitt');
            }
            $this->addCssFile('admin.css');
            $this->addCssFile('magnific-popup.css');
        }

        $this->MasterView = 'admin';
        parent::initialize();
    }

    /**
     * Build and add the Dashboard's side navigation menu.
     *
     * @since 2.0.0
     * @access public
     *
     */
    public function addSideMenu() {
        // Only add to the assets if this is not a view-only request
        if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
	    // Configure SideMenu module.
	    // The SideMenuModule is deprecated, the addToNavModule ports the data from the SideMenuModule to the NavModule.
	    $sideMenu = new SideMenuModule($this);

	    $nav = new NavModule();
            $nav->setView('nav-dashboard');

            $this->EventArguments['Nav'] = $nav;
	    $this->EventArguments['SideMenu'] = $sideMenu;

	    $this->fireEvent('GetAppSettingsMenuItems');
	    $sideMenu->Sort = c('Garden.DashboardMenu.Sort');

            // Add the module
	    $sideMenu->addToNavModule($nav);
            $this->addModule($nav, 'Panel');
        }
    }
}
