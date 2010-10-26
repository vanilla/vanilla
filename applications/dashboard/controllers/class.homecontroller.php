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
 * Dashboard Home Controller
 */
class HomeController extends Gdn_Controller {
   
   public function Initialize() {
      $this->Head = new HeadModule($this);
      $this->AddJsFile('jquery.js');
      $this->AddJsFile('jquery.livequery.js');
      $this->AddJsFile('jquery.form.js');
      $this->AddJsFile('jquery.popup.js');
      $this->AddJsFile('jquery.gardenhandleajaxform.js');
      $this->AddJsFile('global.js');
      $this->AddCssFile('admin.css');
      $this->MasterView = 'empty';
      parent::Initialize();
   }

   /**
    * The dashboard welcome message.
    */
   public function Index() {
      $this->View = 'FileNotFound';
      $this->Render();
   }
   
   /**
    * A standard 404 File Not Found error message is delivered when this action
    * is encountered.
    */
   public function FileNotFound() {
      if ($this->DeliveryMethod() == DELIVERY_METHOD_XHTML)
         $this->Render();
      else
         $this->RenderException(NotFoundException());
   }
   
   public function UpdateMode() {
      $this->Render();
   }

   public function Deleted() {
      $this->Render();
   }
   
   public function TermsOfService() {
      $this->Render();
   }
   
   public function PrivacyPolicy() {
      $this->Render();
   }
   
   public function Permission() {
      if ($this->DeliveryMethod() == DELIVERY_METHOD_XHTML)
         $this->Render();
      else
         $this->RenderException(PermissionException());
   }
   
}