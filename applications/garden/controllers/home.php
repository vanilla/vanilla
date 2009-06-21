<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Mark O'Sullivan
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Mark O'Sullivan at mark [at] lussumo [dot] com
*/

/// <summary>
/// Garden Home Controller
/// </summary>
class HomeController extends GardenController {
   
   /// <summary>
   /// The garden welcome message.
   /// </summary>
   public function Index() {
      $this->Render();
   }
   
   /// <summary>
   /// A standard 404 File Not Found error message is delivered when this action
   /// is encountered.
   /// </summary>
   public function FileNotFound() {
      $this->Render();
   }
   
   public function TermsOfService() {
      $this->Render();
   }
   
   public function PrivacyPolicy() {
      $this->Render();
   }
   
   /// <summary>
   /// Displays a message telling the user that their registration will be
   /// reviewed by an administrator.
   /// </summary>
   public function RegistrationUnderApproval() {
      $this->Render();
   }

   public function Permission() {
      $this->Render();
   }
}