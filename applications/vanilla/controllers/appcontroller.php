<?php if (!defined('APPLICATION')) exit();

class VanillaController extends Controller {
   
   public function Initialize() {
      if ($this->DeliveryType() == DELIVERY_TYPE_ALL) {
         $this->Head = new HeadModule($this);
         $this->Head->AddScript('js/library/jquery.js');
         $this->Head->AddScript('js/library/jquery.livequery.js');
         $this->Head->AddScript('js/library/jquery.form.js');
         $this->Head->AddScript('js/library/jquery.popup.js');
         $this->Head->AddScript('js/library/jquery.menu.js');
         $this->Head->AddScript('js/library/jquery.gardenhandleajaxform.js');
         $this->Head->AddScript('js/global.js');
      }
      
      $this->AddCssFile('default.screen.css');
      $this->AddCssFile('menu.screen.css');
      $this->AddCssFile('popup.screen.css');
      $GuestModule = new GuestModule($this);
      $GuestModule->MessageCode = "It looks like you're new here. If you want to take part in the discussions, click one of these buttons!";
      $this->AddModule($GuestModule);
      parent::Initialize();
   }
   
   public function AddSideMenu($CurrentUrl) {
      // Only add to the assets if this is not a view-only request
      if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
         $SideMenu = new Gdn_MenuModule($this);
         $SideMenu->HtmlId = '';
         $SideMenu->CssClass = 'SideMenu';
         $this->EventArguments['SideMenu'] = &$SideMenu;
         $this->FireEvent('GetAppSettingsMenuItems');
         $this->AddModule($SideMenu, 'Panel');
      }
   }
}