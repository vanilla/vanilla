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
 * Conversations Controller
 *
 * @package Conversations
 */
 
/**
 * Master controller for Conversations for others to extend.
 *
 * @since 2.0.0
 * @package Conversations
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
      if (!Gdn::Session()->IsValid())
         Redirect('/entry/signin?Target='.urlencode($this->SelfUrl));

      if ($this->DeliveryType() == DELIVERY_TYPE_ALL) {
         $this->Head = new HeadModule($this);
         $this->AddJsFile('jquery.js');
         $this->AddJsFile('jquery.livequery.js');
         $this->AddJsFile('jquery.form.js');
         $this->AddJsFile('jquery.popup.js');
         $this->AddJsFile('jquery.gardenhandleajaxform.js');
         $this->AddJsFile('jquery.autogrow.js');
         $this->AddJsFile('jquery.autocomplete.js');
         $this->AddJsFile('global.js');
         $this->AddJsFile('conversations.js');
      }
      
      $this->AddCssFile('style.css');
      $this->AddCssFile('conversations.css');
      parent::Initialize();
   }
}