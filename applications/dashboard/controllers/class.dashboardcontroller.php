<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/
/**
 * Dashboard Controller
 *
 * @package Dashboard
 */
 
/**
 * Master application controller for Dashboard, extended by most others.
 *
 * @since 2.0.0
 * @package Dashboard
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
      $this->AddJsFile('global.js');
      
      if (in_array($this->ControllerName, array('profilecontroller', 'activitycontroller'))) {
         $this->AddCssFile('style.css');
      } else {
         if (!C('Garden.Cdns.Disable', FALSE))
            $this->AddCssFile('http://fonts.googleapis.com/css?family=Rokkitt');
         $this->AddCssFile('admin.css');
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