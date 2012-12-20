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
 * Renders the "You should register or sign in" panel box.
 */
class GuestModule extends Gdn_Module {
   
   public $MessageCode = 'GuestModule.Message';
   public $MessageDefault = "It looks like you're new here. If you want to get involved, click one of these buttons!";
   
   public function __construct($Sender = '', $ApplicationFolder = FALSE) {
      if (!$ApplicationFolder)
         $ApplicationFolder = 'Dashboard';
      parent::__construct($Sender, $ApplicationFolder);
   }
   
   public function AssetTarget() {
      return 'Panel';
   }
   
   public function ToString() {
      if (!Gdn::Session()->IsValid() && C('Garden.Modules.ShowGuestModule'))
         return parent::ToString();

      return '';
   }   

}