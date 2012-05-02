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
 * Renders recently active discussions
 */
class DiscussionsModule extends Gdn_Module {
   public $Limit = 10;
   public $Prefix = 'Discussion';
   
   public function __construct() {
      parent::__construct();
      $this->_ApplicationFolder = 'vanilla';
   }
   
   public function GetData($Limit = FALSE) {
      if (!$Limit)
         $Limit = $this->Limit;
      
      $DiscussionModel = new DiscussionModel();
      $this->SetData('Discussions', $DiscussionModel->Get(0, $Limit, array('Announce' => 'all')));
   }

   public function AssetTarget() {
      return 'Panel';
   }

   public function ToString() {
      if (!$this->Data('Discussions')) {
         $this->GetData();
      }
      
      require_once Gdn::Controller()->FetchViewLocation('helper_functions', 'Discussions', 'Vanilla');
      
      return parent::ToString();
   }
}