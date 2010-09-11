<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class UtilityController extends DashboardController {
   
   public $Uses = array('Form');
   
   public function Sort() {
      $Session = Gdn::Session();
      $TransientKey = GetPostValue('TransientKey', '');
      $Target = GetPostValue('Target', '');
      if ($Session->ValidateTransientKey($TransientKey)) {
         $TableID = GetPostValue('TableID', FALSE);
         if ($TableID) {
            $Rows = GetPostValue($TableID, FALSE);
            if (is_array($Rows)) {
               try {
                  $Table = str_replace('Table', '', $TableID);
                  $TableModel = new Gdn_Model($Table);
                  foreach ($Rows as $Sort => $ID) {
                     $TableModel->Update(array('Sort' => $Sort), array($Table.'ID' => $ID));
                  }
               } catch (Exception $ex) {
                  $this->Form->AddError($ex->getMessage());
               }
            }
         }
      }
      if ($this->DeliveryType() != DELIVERY_TYPE_BOOL)
         Redirect($Target);
         
      $this->Render();
   }
   
   /**
    * Allows the setting of data into one of two serialized data columns on the
    * user table: Preferences and Attributes. The method expects "Name" &
    * "Value" to be in the $_POST collection. This method always saves to the
    * row of the user id performing this action (ie. $Session->UserID). The
    * type of property column being saved should be specified in the url:
    *  ie. /dashboard/utility/set/preference/name/value/transientKey
    *  or /dashboard/utility/set/attribute/name/value/transientKey
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
   
   public function Structure($AppName = 'all', $CaptureOnly = '1', $Drop = '0', $Explicit = '0') {
      $this->Permission('Garden.AdminUser.Only');
      $Files = array();
      $AppName = $AppName == '' ? 'all': $AppName;
      if ($AppName == 'all') {
			// Load all application structure files.
			$ApplicationManager = new Gdn_ApplicationManager();
			$Apps = $ApplicationManager->EnabledApplications();
			$AppNames = ConsolidateArrayValuesByKey($Apps, 'Folder');
			foreach ($AppNames as $AppName) {
				$Files[] = CombinePaths(array(PATH_APPLICATIONS, $AppName, 'settings', 'structure.php'), DS);
			}
			$AppName = 'all';
      } else {
			 // Load that specific application structure file.
         $Files[] = CombinePaths(array(PATH_APPLICATIONS, $AppName, 'settings', 'structure.php'), DS);
      }
      $Validation = new Gdn_Validation();
      $Database = Gdn::Database();
      $Drop = $Drop == '0' ? FALSE : TRUE;
      $Explicit = $Explicit == '0' ? FALSE : TRUE;
      $CaptureOnly = !($CaptureOnly == '0');
      $Structure = Gdn::Structure();
      $Structure->CaptureOnly = $CaptureOnly;
      $SQL = Gdn::SQL();
      $SQL->CaptureModifications = $CaptureOnly;
      $this->SetData('CaptureOnly', $Structure->CaptureOnly);
      $this->SetData('Drop', $Drop);
      $this->SetData('Explicit', $Explicit);
      $this->SetData('ApplicationName', $AppName);
      $this->SetData('Status', '');
      $FoundStructureFile = FALSE;
      foreach ($Files as $File) {
         if (file_exists($File)) {
			   $FoundStructureFile = TRUE;
			   try {
			      include($File);
			   } catch (Exception $Ex) {
			      $this->Form->AddError($Ex);
			   }
			}
			if (property_exists($Structure->Database, 'CapturedSql'))
			   $this->SetData('CapturedSql', (array)$Structure->Database->CapturedSql);
			else
			   $this->SetData('CapturedSql', array());
      }
      if ($this->Form->ErrorCount() == 0 && !$CaptureOnly && $FoundStructureFile)
         $this->SetData('Status', 'The structure was successfully executed.');

		$this->AddSideMenu('dashboard/settings/configure');
      $this->AddCssFile('admin.css');
      $this->SetData('Title', T('Database Structure Upgrades'));
      $this->Render();
   }
   
   // Because you cannot send xmlhttprequests across domains, we need to use
   // a proxy to check for updates.
   public function UpdateProxy() {
      $Fields = $_POST;
      foreach ($Fields as $Field => $Value) {
         if (get_magic_quotes_gpc()) {
            if (is_array($Value)) {
               $Count = count($Value);
               for ($i = 0; $i < $Count; ++$i) {
                  $Value[$i] = stripslashes($Value[$i]);
               }
            } else {
               $Value = stripslashes($Value);
            }
            $Fields[$Field] = $Value;
         }
      }
      
		$UpdateCheckUrl = C('Garden.UpdateCheckUrl', 'http://vanillaforums.org/addons/update');
      echo ProxyRequest($UpdateCheckUrl.'?'.http_build_query($Fields));
      $Database = Gdn::Database();
      $Database->CloseConnection();
   }

   public function UpdateResponse() {
      // Get the message, response, and transientkey
      $Messages = TrueStripSlashes(GetValue('Messages', $_POST));
      $Response = TrueStripSlashes(GetValue('Response', $_POST));
      $TransientKey = GetIncomingValue('TransientKey', '');
      
      // If the key validates
      $Session = Gdn::Session();
      if ($Session->ValidateTransientKey($TransientKey)) {
         // If messages wasn't empty
         if ($Messages != '') {
            // Unserialize them & save them if necessary
            $Messages = Gdn_Format::Unserialize($Messages);
            if (is_array($Messages)) {
               $MessageModel = new MessageModel();
               foreach ($Messages as $Message) {
                  // Check to see if it already exists, and if not, add it.
                  if (is_object($Message))
                     $Message = Gdn_Format::ObjectAsArray($Message);

                  $Content = ArrayValue('Content', $Message, '');
                  if ($Content != '') {
                     $Data = $MessageModel->GetWhere(array('Content' => $Content));
                     if ($Data->NumRows() == 0) {
                        $MessageModel->Save(array(
                           'Content' => $Content,
                           'AllowDismiss' => ArrayValue('AllowDismiss', $Message, '1'),
                           'Enabled' => ArrayValue('Enabled', $Message, '1'),
                           'Application' => ArrayValue('Application', $Message, 'Dashboard'),
                           'Controller' => ArrayValue('Controller', $Message, 'Settings'),
                           'Method' => ArrayValue('Method', $Message, ''),
                           'AssetTarget' => ArrayValue('AssetTarget', $Message, 'Content'),
                           'CssClass' => ArrayValue('CssClass', $Message, '')
                        ));
                     }
                  }
               }
            }
         }

         // Save some info to the configuration file
         $Save = array();

         // If the response wasn't empty, save it in the config
         if ($Response != '')
            $Save['Garden.RequiredUpdates'] = Gdn_Format::Unserialize($Response);
      
         // Record the current update check time in the config.
         $Save['Garden.UpdateCheckDate'] = time();
         SaveToConfig($Save);
      }
   }

   public function SetClientHour($ClientHour = '', $TransientKey = '') {
      $this->_DeliveryType = DELIVERY_TYPE_BOOL;
      $Session = Gdn::Session();
      $Success = FALSE;
      if (
			is_numeric($ClientHour)
			&& $Session->UserID > 0
         && $Session->ValidateTransientKey($TransientKey)
      ) {
         $UserModel = Gdn::UserModel();
			$HourOffset = $ClientHour - date('G', time());
			$UserModel->Update(array('HourOffset' => $HourOffset), array('UserID' => $Session->UserID));
			$Success = TRUE;
      }
         
      $this->Render();
   }
}