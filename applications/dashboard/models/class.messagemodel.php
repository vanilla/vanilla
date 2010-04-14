<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class MessageModel extends Gdn_Model {
   
   /**
    * Class constructor. Defines the related database table name.
    */
   public function __construct() {
      parent::__construct('Message');
   }
   
   /**
    * Returns a single message object for the specified id or FALSE if not found.
    *
    * @param int The MessageID to filter to.
    */
   public function GetID($MessageID) {
      $Message = $this->GetWhere(array('MessageID' => $MessageID))->FirstRow();
      $Message = $this->DefineLocation($Message);
      return $Message;
   }
   
   public function DefineLocation($Message) {
      if (is_object($Message)) {
         if ($Message->Controller == 'Base') {
            $Message->Location = 'Base';
         } else {
            $Message->Location = $Message->Application;
            if (!StringIsNullOrEmpty($Message->Controller)) $Message->Location .= '/'.$Message->Controller;
            if (!StringIsNullOrEmpty($Message->Method)) $Message->Location .= '/'.$Message->Method;
         }
      }
      return $Message;
   }
   
   public function GetMessagesForLocation($Location) {
      $Session = Gdn::Session();
      $Prefs = $Session->GetPreference('DismissedMessages', array());
      if (count($Prefs) == 0)
         $Prefs[] = 0;
         
      list($Application, $Controller, $Method) = explode('/', $Location);
      return $this->SQL
         ->Select()
         ->From('Message')
         ->Where('Enabled', '1')
         ->BeginWhereGroup()
         ->Where('Controller', 'Base')
         ->BeginWhereGroup()
         ->OrWhere('Application', $Application)
         ->Where('Controller', $Controller)
         ->Where('Method', $Method)
         ->EndWhereGroup()
         ->EndWhereGroup()
         ->WhereNotIn('MessageID', $Prefs)
         ->OrderBy('Sort', 'asc')
         ->Get();
   }
   
   /**
    * Returns a distinct array of controllers that have enabled messages.
    */
   public function GetEnabledLocations() {
      $Data = $this->SQL
         ->Select('Application,Controller,Method')
         ->From('Message')
         ->Where('Enabled', '1')
         ->GroupBy('Application,Controller,Method')
         ->Get();
         
      $Locations = array();
      foreach ($Data as $Row) {
         if ($Row->Controller == 'Base') {
            $Locations[] = 'Base';
         } else {
            $Location = $Row->Application;
            if ($Row->Controller != '') $Location .= '/' . $Row->Controller;
            if ($Row->Method != '') $Location .= '/' . $Row->Method;
            $Locations[] = $Location;
         }
      }
      return $Locations;
   }
   
   public function Save($FormPostValues, $Settings = FALSE) {
      // The "location" is packed into a single input with a / delimiter. Need to explode it into three different fields for saving
      $Location = ArrayValue('Location', $FormPostValues, '');
      if ($Location != '') {
         $Location = explode('/', $Location);
         if ($Location[0] == 'Base') {
            $FormPostValues['Controller'] = 'Base';
         } else {
            if (count($Location) >= 1) $FormPostValues['Application'] = $Location[0];
            if (count($Location) >= 2) $FormPostValues['Controller'] = $Location[1];
            if (count($Location) >= 3) $FormPostValues['Method'] = $Location[2];
         }
      }

      return parent::Save($FormPostValues, $Settings);
   }
   
   public function SetMessageCache() {
      // Retrieve an array of all controllers that have enabled messages associated
      SaveToConfig('Garden.Messages.Cache', $this->GetEnabledLocations());
   }
}