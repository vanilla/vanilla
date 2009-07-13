<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Mark O'Sullivan
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Mark O'Sullivan at mark [at] lussumo [dot] com
*/

/**
 * Garden Utility Controller
 */
class UtilityController extends GardenController {
   
   public $Uses = array('Form');
   
   public function Sort() {
      $this->_DeliveryType = DELIVERY_TYPE_BOOL;
      $Success = FALSE;
      if ($this->Form->AuthenticatedPostBack()) {
         $TableID = GetPostValue('TableID', FALSE);
         if ($TableID) {
            $Rows = GetPostValue($TableID, FALSE);
            if (is_array($Rows)) {
               try {
                  $Table = str_replace('Table', '', $TableID);
                  $TableModel = new Model($Table);
                  foreach ($Rows as $Sort => $ID) {
                     $TableModel->Update(array('Sort' => $Sort), array($Table.'ID' => $ID));
                  }
                  $Success = TRUE;
               } catch (Exception $ex) {
                  $this->Form->AddError($ex->getMessage());
               }
            }
         }
      }
      if (!$Success)
         $this->Form->AddError('ErrorBool');
         
      $this->Render();
   }
   
   /**
    * Allows the setting of data into one of two serialized data columns on the
    * user table: Preferences and Attributes. The method expects "Name" &
    * "Value" to be in the $_POST collection. This method always saves to the
    * row of the user id performing this action (ie. $Session->UserID). The
    * type of property column being saved should be specified in the url:
    *  ie. /garden/utility/set/preference/name/value/transientKey
    *  or /garden/utility/set/attribute/name/value/transientKey
    *
    * @param string The type of value being saved: preference or attribute.
    * @param string The name of the property being saved.
    * @param string The value of the property being saved.
    * @param string A unique transient key to authenticate that the user intended to perform this action.
    */
   public function Set($UserPropertyColumn = '', $Name = '', $Value = '', $TransientKey = '') {
      $this->_DeliveryType = DELIVERY_TYPE_BOOL;
      $Session = Gdn::Session();
      $Success = FALSE;
      if (
         in_array($UserPropertyColumn, array('preference', 'attribute'))
         && $Name != ''
         && $Value != ''
         && $Session->UserID > 0
         && $Session->ValidateTransientKey($TransientKey)
      ) {
         $UserModel = Gdn::Factory("UserModel");
         $Method = $UserPropertyColumn == 'preference' ? 'SavePreference' : 'SaveAttribute';
         $Success = $UserModel->$Method($Session->UserID, $Name, $Value) ? 'TRUE' : 'FALSE';
      }
      
      if (!$Success)
         $this->Form->AddError('ErrorBool');
      
      // Redirect back where the user came from if necessary
      if ($this->_DeliveryType == DELIVERY_TYPE_ALL)
         Redirect($_SERVER['HTTP_REFERER']);
      else
         $this->Render();
   }
   
   // TODO: REMOVE THIS METHOD - DEBUG PURPOSES ONLY
   public function Structure($AppName = 'garden', $Drop = '0', $Explicit = '0') {
      $this->Permission('Garden.AdminUser.Only');
      $File = CombinePaths(array(PATH_APPLICATIONS, $AppName, 'settings', 'structure.php'), DS);
      if (file_exists($File)) {
         $Validation = new Gdn_Validation();
         $Database = Gdn::Database();
         $Construct = $Database->Structure();
         $Drop = $Drop == '0' ? FALSE : TRUE;
         $Explicit = $Explicit == '0' ? FALSE : TRUE;
         try {
            include($File);
         } catch (Exception $ex) {
            $this->Form->AddError(strip_tags($ex->getMessage()));
         }
         if ($this->Form->ErrorCount() == 0)
            echo 'Success';
         else
            echo $this->Form->Errors();
      } else {
         echo 'File not found';
      }
   }
   
   public function UsernameAvailable($Name = '') {
      $this->_DeliveryType = DELIVERY_TYPE_BOOL;
      $Available = TRUE;
      if ($Name != '') {
         $UserModel = Gdn::UserModel();
         if ($UserModel->Get($Name))
            $Available = FALSE;
      }
      if (!$Available)
         $this->Form->AddError('Username unavailable');
         
      $this->Render();
   }
   
   public function SqlDrriverTest() {
      $this->Render();
   }
}