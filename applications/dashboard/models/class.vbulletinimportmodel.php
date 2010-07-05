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
 * Object for doing specific actions to a vbulletin import.
 */
class vBulletinImportModel extends Gdn_Model {
   /**
    * @var ImportModel
    */
   var $ImportModel = null;

   public function AfterImport() {
      // Set up the routes to redirect from their older counterparts.
      $Router = Gdn::Router();
      $Router->SetRoute('forumdisplay\.php\?f=(\d+)', 'vanilla/categories/$1', 'Permanent');
      $Router->SetRoute('showthread\.php\?t=(\d+)', 'vanilla/discussion/$1', 'Permanent');
      $Router->SetRoute('member\.php\?u=(\d+)', 'dashboard/profile/$1/x', 'Permanent');
   }
}