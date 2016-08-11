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
        $this->addJsFile('jquery.popin.js');
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
     * EXACT COPY OF VanillaController::addSideMenu(). KEEP IN SYNC.
     * Dashboard is getting rebuilt. No wisecracks about DRY in the meantime.
     *
     * @since 2.0.0
     * @access public
     *
     * @param string|bool $CurrentUrl Path to current location; used to highlight correct item in menu.
     */
    public function addSideMenu($CurrentUrl = false) {
        if (!$CurrentUrl) {
            $CurrentUrl = strtolower($this->SelfUrl);
        }

        // Only add to the assets if this is not a view-only request
        if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
            $SideMenu = new SideMenuModule($this);

            // Add the heading here so that they sort properly.
            $SideMenu->addItem('Dashboard', t('Dashboard'), false, array('class' => 'Dashboard'));
            $SideMenu->addItem('Appearance', t('Appearance'), false, array('class' => 'Appearance'));
            $SideMenu->addItem('Users', t('Users'), false, array('class' => 'Users'));
            $SideMenu->addItem('Moderation', t('Moderation'), false, array('class' => 'Moderation'));

            // Hook for initial setup. Do NOT use this for addons.
            $this->EventArguments['SideMenu'] = $SideMenu;
            $this->fireEvent('earlyAppSettingsMenuItems');

            // Module setup.
            $SideMenu->HtmlId = '';
            $SideMenu->highlightRoute($CurrentUrl);
            $SideMenu->Sort = c('Garden.DashboardMenu.Sort');

            // Hook for adding to menu.
            $this->fireEvent('GetAppSettingsMenuItems');

            // Add the module
            $this->addModule($SideMenu, 'Panel');
        }
    }
}
