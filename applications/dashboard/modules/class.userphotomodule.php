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
 * Renders a user's photo (if they've uploaded one).
 */
class UserPhotoModule extends Gdn_Module {
   
   public $User;
   
   public function __construct(&$Sender = '') {
      $this->User = FALSE;
      parent::__construct($Sender);
   }
   
   public function AssetTarget() {
      return 'Panel';
   }

   public function ToString() {
      $Session = Gdn::Session();
		return parent::ToString();
			
		return '';
   }
}