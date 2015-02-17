<?php if (!defined('APPLICATION')) exit();

/**
 * Master application controller for Dashboard, extended by most others.
 *
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
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
   public function Initialize() {
      $this->Head = new HeadModule($this);
      $this->AddJsFile('jquery.js');
      $this->AddJsFile('jquery.livequery.js');
      $this->AddJsFile('jquery.form.js');
      $this->AddJsFile('jquery.popup.js');
      $this->AddJsFile('jquery.gardenhandleajaxform.js');
      $this->AddJsFile('magnific-popup.min.js');
      $this->AddJsFile('jquery.autosize.min.js');
      $this->AddJsFile('global.js');

      if (in_array($this->ControllerName, array('profilecontroller', 'activitycontroller'))) {
         $this->AddCssFile('style.css');
         $this->AddCssFile('vanillicon.css', 'static');
      } else {
         if (!C('Garden.Cdns.Disable', FALSE))
            $this->AddCssFile('https://fonts.googleapis.com/css?family=Rokkitt');
         $this->AddCssFile('admin.css');
         $this->AddCssFile('magnific-popup.css');
      }

      $this->MasterView = 'admin';
      parent::Initialize();
   }

   /**
    * Build and add the Dashboard's side navigation menu.
    *
    * @since 2.0.0
    * @access public
    *
    * @param string $CurrentUrl Used to highlight correct route in menu.
    */
   public function AddSideMenu($CurrentUrl = FALSE) {
		if(!$CurrentUrl)
			$CurrentUrl = strtolower($this->SelfUrl);

      // Only add to the assets if this is not a view-only request
      if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
         // Configure SideMenu module
         $SideMenu = new SideMenuModule($this);
         $SideMenu->EventName = 'GetAppSettingsMenuItems';
         $SideMenu->HtmlId = '';
         $SideMenu->HighlightRoute($CurrentUrl);
			$SideMenu->Sort = C('Garden.DashboardMenu.Sort');

         // Hook for adding to menu
//         $this->EventArguments['SideMenu'] = &$SideMenu;
//         $this->FireEvent('GetAppSettingsMenuItems');

         // Add the module
         $this->AddModule($SideMenu, 'Panel');
      }
   }
}
