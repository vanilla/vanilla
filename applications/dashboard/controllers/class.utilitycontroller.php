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
 * Utility Controller
 *
 * @package Dashboard
 */
 
/**
 * Perform miscellaneous operations for Dashboard.
 *
 * @since 2.0.0
 * @package Dashboard
 */
class UtilityController extends DashboardController {
   /** @var array Models to automatically instantiate. */
   public $Uses = array('Form');
   
   /**
    * Redirect to another page.
    * @since 2.0.18b4
    */
   public function Redirect() {
      $Args = func_get_args();
      $Path = $this->Request->Path();
      if (count($Args) > 0) {
         if (in_array($Args[0], array('http', 'https'))) {
            $Protocal = array_shift($Args);
         } else {
            $Protocal = 'http';
         }
         $Url = $Protocal.'://'.implode($Args, '/');
      } else {
         $Url = Url('/', TRUE);
      }
      
      $Get = $this->Request->Get();
      if (count($Get) > 0) {
         $Query = '?'.http_build_query($Get);
      } else {
         $Query = '';
      }
      
      Redirect($Url.$Query);
   }
   
   /**
    * Set the sort order for data on an arbitrary database table.
    *
    * Expect post values TransientKey, Target (redirect URL), Table (database table name),
    * and TableID (an array of sort order => unique ID).
    *
    * @since 2.0.0
    * @access public
    */
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
    * user table: Preferences and Attributes. 
    *
    * The method expects "Name" & "Value" to be in the $_POST collection. This method always 
    * saves to the row of the user id performing this action (ie. $Session->UserID). The
    * type of property column being saved should be specified in the url:
    * i.e. /dashboard/utility/set/preference/name/value/transientKey
    * or /dashboard/utility/set/attribute/name/value/transientKey
    *
    * @since 2.0.0
    * @access public
    * @param string $UserPropertyColumn The type of value being saved: preference or attribute.
    * @param string $Name The name of the property being saved.
    * @param string $Value The value of the property being saved.
    * @param string $TransientKey A unique transient key to authenticate that the user intended to perform this action.
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
   
   /**
    * Update database structure based on current definitions in each app's structure.php file.
    *
    * @since 2.0.?
    * @access public
    * @param string $AppName Unique app name or 'all' (default).
    * @param int $CaptureOnly Whether to list changes rather than execture (0 or 1).
    * @param int $Drop Whether to drop first (0 or 1).
    * @param int $Explicit Whether to force to only columns currently listed (0 or 1).
    */
   public function Structure($AppName = 'all', $CaptureOnly = '1', $Drop = '0', $Explicit = '0') {
      $this->Permission('Garden.Settings.Manage');
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
      }

      // Run the structure of all of the plugins.
      $Plugins = Gdn::PluginManager()->EnabledPlugins();
      foreach ($Plugins as $PluginKey => $Plugin) {
         $PluginInstance = Gdn::PluginManager()->GetPluginInstance($PluginKey, Gdn_PluginManager::ACCESS_PLUGINNAME);
         if (method_exists($PluginInstance, 'Structure'))
            $PluginInstance->Structure();
      }

      if (property_exists($Structure->Database, 'CapturedSql'))
         $this->SetData('CapturedSql', (array)$Structure->Database->CapturedSql);
      else
         $this->SetData('CapturedSql', array());

      if ($this->Form->ErrorCount() == 0 && !$CaptureOnly && $FoundStructureFile)
         $this->SetData('Status', 'The structure was successfully executed.');

		$this->AddSideMenu('dashboard/settings/configure');
      $this->AddCssFile('admin.css');
      $this->SetData('Title', T('Database Structure Upgrades'));
      $this->Render();
   }
   
   /**
    * Run a structure update on the database.
    *
    * @since 2.0.?
    * @access public
    */
   public function Update() {
      // Check for permission or flood control.
      // These settings are loaded/saved to the database because we don't want the config file storing non/config information.
      $Now = time();
      $LastTime = Gdn::Get('Garden.Update.LastTimestamp', 0);

      if ($LastTime + (60 * 60 * 24) > $Now) {
         // Check for flood control.
         $Count = Gdn::Get('Garden.Update.Count', 0) + 1;
         if ($Count > 5) {
            if (!Gdn::Session()->CheckPermission('Garden.Settings.Manage')) {
               // We are only allowing an update of 5 times every 24 hours.
               throw PermissionException();
            }
         }
      } else {
         $Count = 1;
      }
      Gdn::Set('Garden.Update.LastTimestamp', $Now);
      Gdn::Set('Garden.Update.Count', $Count);
      
      try {
         // Run the structure.
         $UpdateModel = new UpdateModel();
         $UpdateModel->RunStructure();
         $this->SetData('Success', TRUE);
      } catch (Exception $Ex) {
         $this->SetData('Success', FALSE);
      }
      
      if (Gdn::Session()->CheckPermission('Garden.Settings.Manage')) {
         SaveToConfig('Garden.Version', APPLICATION_VERSION);
      }
      
      if ($Target = $this->Request->Get('Target')) {
         Redirect($Target);
      }

      $this->MasterView = 'empty';
      $this->CssClass = 'Home';
      $this->Render();
   }
   
   /**
    * Because people try this a lot and get confused.
    *
    * @since 2.0.18
    * @access public
    */
   public function Upgrade() {
      $this->Update();
   }
   
   /**
    * Signs of life.
    *
    * @since 2.0.?
    * @access public
    */
   public function Alive() {
      $this->SetData('Success', TRUE);
      $this->MasterView = 'empty';
      $this->CssClass = 'Home';
      $this->Render();
   }
   
   /**
    * Because you cannot send xmlhttprequests across domains, we need to use
    * a proxy to check for updates.
    *
    * @since 2.0.?
    * @access public
    */
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
   
   /**
    * What the mothership said about update availability.
    *
    * @since 2.0.?
    * @access public
    */
   public function UpdateResponse() {
      // Get the message, response, and transientkey
      $Response = TrueStripSlashes(GetValue('Response', $_POST));
      $TransientKey = GetIncomingValue('TransientKey', '');
      
      // If the key validates
      $Session = Gdn::Session();
      if ($Session->ValidateTransientKey($TransientKey)) {
         // Save some info to the configuration file
         $Save = array();

         // If the response wasn't empty, save it in the config
         if ($Response != '')
            $Save['Garden.RequiredUpdates'] = @json_decode($Response);
      
         // Record the current update check time in the config.
         $Save['Garden.UpdateCheckDate'] = time();
         SaveToConfig($Save);
      }
   }
   
   /**
    * Set the user's timezone (hour offset).
    *
    * @since 2.0.0
    * @access public
    * @param string $ClientDate Client-reported datetime.
    * @param string $TransientKey Security token.
    */
   public function SetClientHour($ClientDate = '', $TransientKey = '') {
      $this->_DeliveryType = DELIVERY_TYPE_BOOL;
      $Session = Gdn::Session();
      $Success = FALSE;

      $ClientTimestamp = Gdn_Format::ToTimestamp($ClientDate);

      if (
			is_numeric($ClientTimestamp)
			&& $Session->UserID > 0
         && $Session->ValidateTransientKey($TransientKey)
      ) {
         $UserModel = Gdn::UserModel();
			$HourOffset = $ClientTimestamp - time();
         $HourOffset = round($HourOffset / 3600);
         
			$UserModel->SetField($Session->UserID, 'HourOffset', $HourOffset);
			$Success = TRUE;
      }
         
      $this->Render();
   }
	
	/**
    * Grab a feed from the mothership.
    *
    * @since 2.0.?
    * @access public
    * @param string $Type Type of feed.
    * @param int $Length Number of items to get.
    * @param string $FeedFormat How we want it (valid formats are 'normal' or 'sexy'. OK, not really).
    */
	public function GetFeed($Type = 'news', $Length = 5, $FeedFormat = 'normal') {
		echo file_get_contents('http://vanillaforums.org/vforg/home/getfeed/'.$Type.'/'.$Length.'/'.$FeedFormat.'/?DeliveryType=VIEW');
		$this->DeliveryType(DELIVERY_TYPE_NONE);
      $this->Render();
	}
}