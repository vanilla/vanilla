<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Mark O'Sullivan
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Mark O'Sullivan at mark [at] lussumo [dot] com
*/

class ConversationsController extends Gdn_Controller {
   
   /// <summary>
   /// Returns an array of pages that contain settings information for this
   /// application.
   /// </summary>
   public function GetSettingsPages(&$Menu) {
      // There are no configuration pages for Conversations
   }
   
   public function __construct() {
      parent::__construct();
   }
   
   public function Initialize() {
      $this->Permission('Garden.SignIn.Allow');
      if ($this->DeliveryType() == DELIVERY_TYPE_ALL) {
         $this->Head = new HeadModule($this);
         $this->Head->AddScript('js/library/jquery.js');
         $this->Head->AddScript('js/library/jquery.livequery.js');
         $this->Head->AddScript('js/library/jquery.form.js');
         $this->Head->AddScript('js/library/jquery.popup.js');
         $this->Head->AddScript('js/library/jquery.menu.js');
         $this->Head->AddScript('js/library/jquery.gardenhandleajaxform.js');
         $this->Head->AddScript('js/library/jquery.gardenmorepager.js');
         $this->Head->AddScript('js/library/jquery.autogrow.js');
         $this->Head->AddScript('js/library/jquery.autocomplete.js');
         $this->Head->AddScript('js/global.js');
         $this->Head->AddScript('/applications/conversations/js/conversations.js');
      }
      
      $this->AddCssFile('default.screen.css');
      $this->AddCssFile('menu.screen.css');
      $this->AddCssFile('popup.screen.css');
      $this->AddCssFile('vanilla.screen.css');      
      $this->AddCssFile('conversations.screen.css');
      parent::Initialize();
   }
}