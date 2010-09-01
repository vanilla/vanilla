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
   
   private $_SpecialLocations = array('[Base]', '[Admin]', '[NonAdmin]');
   
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
         if (in_array($Message->Controller, $this->_SpecialLocations)) {
            $Message->Location = $Message->Controller;
         } else {
            $Message->Location = $Message->Application;
            if (!StringIsNullOrEmpty($Message->Controller)) $Message->Location .= '/'.$Message->Controller;
            if (!StringIsNullOrEmpty($Message->Method)) $Message->Location .= '/'.$Message->Method;
         }
      }
      return $Message;
   }
   
   public function GetMessagesForLocation($Location, $Exceptions = array('[Base]')) {
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
         ->WhereIn('Controller', $Exceptions)
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
         if (in_array($Row->Controller, $this->_SpecialLocations)) {
            $Locations[] = $Row->Controller;
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
         $Application = GetValue(0, $Location, '');
         if (in_array($Application, $this->_SpecialLocations)) {
            $FormPostValues['Controller'] = $Application;
         } else {
            $FormPostValues['Application'] = $Application;
            $FormPostValues['Controller'] = GetValue(1, $Location, '');
            $FormPostValues['Method'] = GetValue(2, $Location, '');
         }
      }

      return parent::Save($FormPostValues, $Settings);
   }
   
   public function SetMessageCache() {
      // Retrieve an array of all controllers that have enabled messages associated
      SaveToConfig('Garden.Messages.Cache', $this->GetEnabledLocations());
   }
}