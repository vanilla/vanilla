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
 * Session Controller
 *
 * @package Dashboard
 */
 
/**
 * Convenience access to current user's session.
 *
 * @since 2.0.?
 * @package Dashboard
 */
class SessionController extends DashboardController {
   /**
    * Stash a value in the user's session, or unstash it if no value was provided to stash.
    *
    * Looks for Name and Value POST/GET variables to pass along to Gdn_Session.
    */
   public function Stash() {
      $this->DeliveryType(DELIVERY_TYPE_BOOL);
      $this->DeliveryMethod(DELIVERY_METHOD_JSON);
      $Name = TrueStripSlashes(GetValue('Name', $_POST, ''));
      $Value = TrueStripSlashes(GetValue('Value', $_POST, ''));
      $Response = Gdn::Session()->Stash($Name, $Value);
      if ($Name != '' && $Value == '')
         $this->SetJson('Unstash', $Response);

      $this->Render();
   }
}