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
 
set_time_limit(0);

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
    * Gather all of the global styles together.
    * @param string $Filename 
    * @since 2.1
    */
   public function Css($Basename, $Revision) {
      $AssetModel = new AssetModel();
      $AssetModel->ServeCss($Basename, $Revision);
   }
   
   public function Initialize() {
      parent::Initialize();
      Gdn_Theme::Section('Dashboard');
   }
   
//   /**
//    * Call a method on the given model.
//    */
//   public function Model() {
//      $this->Permission('Garden.Settings.Manage');
//      
//      $this->DeliveryMethod(DELIVERY_METHOD_JSON);
//      $this->DeliveryType(DELIVERY_TYPE_DATA);
//      
//      $Args = func_get_args();
//      
//      // Check to see if we have a model.
//      $ModelName = StringEndsWith(array_shift($Args), 'Model', TRUE, TRUE);
//      $ModelName = ucfirst($ModelName).'Model';
//      if (!class_exists($ModelName)) {
//         throw NotFoundException($ModelName);
//      }
//      
//      // Check for json/xml style extension.
//      if (count($Args)) {
//         $LastArg = $Args[count($Args) - 1];
//         $Extension = strrchr($LastArg, '.');
//         if ($Extension) {
//            $Args[count($Args) - 1] = substr($LastArg, 0, -strlen($Extension));
//            $Extension = strtolower($Extension);
//            if ($Extension == '.xml')
//               $this->DeliveryMethod(DELIVERY_METHOD_XML);
//         }
//      }
//      
//      // Instantiate the model.
//      $Model = new $ModelName();
//      $MethodName = array_shift($Args);
//      
//      // Reflect the arguments.
//      $Callback = array($Model, $MethodName);
//      
//      if ($this->Request->Get('help')) {
//         $this->SetData('Model', get_class($Model));
//         if ($MethodName) {
//            if (!method_exists($Model, $MethodName)) {
//               throw NotFoundException($ModelName.'->'.$MethodName.'()');
//            }
//            $this->SetData('Method', $MethodName);
//            $Meth = new ReflectionMethod($Callback[0], $Callback[1]);
//            $MethArgs = $Meth->getParameters();
//            $Args = array();
//            foreach ($MethArgs as $Index => $MethArg) {
//               $ParamName = $MethArg->getName();
//
//               if ($MethArg->isDefaultValueAvailable())
//                  $Args[$ParamName] = $MethArg->getDefaultValue();
//               else
//                  $Args[$ParamName] = 'REQUIRED';
//            }
//            $this->SetData('Args', $Args);
//         } else {
//            $Class = new ReflectionClass($Model);
//            $Meths = $Class->getMethods();
//            $Methods = array();
//            foreach ($Meths as $Meth) {
//               $MethodName = $Meth->getName();
//               if (StringBeginsWith($MethodName, '_'))
//                  continue;
//               
//               $MethArgs = $Meth->getParameters();
//               $Args = array();
//               foreach ($MethArgs as $Index => $MethArg) {
//                  $ParamName = $MethArg->getName();
//
//                  if ($MethArg->isDefaultValueAvailable())
//                     $Args[$ParamName] = $MethArg->getDefaultValue();
//                  else
//                     $Args[$ParamName] = 'REQUIRED';
//               }
//               $Methods[$MethodName] = array('Method' => $MethodName, 'Args' => $Args);
//            }
//            $this->SetData('Methods', $Methods);
//         }
//      } else {
//         if (!method_exists($Model, $MethodName)) {
//            throw NotFoundException($ModelName.'->'.$MethodName.'()');
//         }
//         
//         $MethodArgs = ReflectArgs($Callback, $this->Request->Get(), $Args);
//         
//         $Result = call_user_func_array($Callback, $MethodArgs);
//
//         if (is_array($Result))
//            $this->Data = $Result;
//         elseif (is_a($Result, 'Gdn_DataSet')) {
//            $Result = $Result->ResultArray();
//            $this->Data = $Result;
//         } elseif (is_a($Result, 'stdClass'))
//            $this->Data = (array)$Result;
//         else
//            $this->SetData('Result', $Result);
//      }
//      
//      $this->Render();
//   }
   
   /**
    * Redirect to another page.
    * @since 2.0.18b4
    */
//   public function Redirect() {
//      $Args = func_get_args();
//      $Path = $this->Request->Path();
//      if (count($Args) > 0) {
//         if (in_array($Args[0], array('http', 'https'))) {
//            $Protocal = array_shift($Args);
//         } else {
//            $Protocal = 'http';
//         }
//         $Url = $Protocal.'://'.implode($Args, '/');
//      } else {
//         $Url = Url('/', TRUE);
//      }
//      
//      $Get = $this->Request->Get();
//      if (count($Get) > 0) {
//         $Query = '?'.http_build_query($Get);
//      } else {
//         $Query = '';
//      }
//      
//      Redirect($Url.$Query);
//   }
   
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
      $this->Permission('Garden.Settings.Manage');
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
   
   public function Sprites() {
      $this->RemoveCssFile('admin.css');
      $this->AddCssFile('style.css');
      $this->MasterView = 'default';
      
      $this->CssClass = 'SplashMessage NoPanel';
      $this->SetData('_NoMessages', TRUE);
      $this->SetData('Title', 'Sprite Sheet');
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
      
      try {
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
      } catch (PermissionException $Ex) {
         return;
      } catch (Exception $Ex) {}
      
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
      
      $this->FireEvent('AfterUpdate');

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
      
      $this->FireEvent('Alive');
      
      $this->Render();
   }
   
   /**
    * Set the user's timezone (hour offset).
    *
    * @since 2.0.0
    * @access public
    * @param string $ClientDate Client-reported datetime.
    * @param string $TransientKey Security token.
    */
   public function SetClientHour($ClientHour = '', $TransientKey = '') {
      $this->_DeliveryType = DELIVERY_TYPE_BOOL;
      $Success = FALSE;

      if (is_numeric($ClientHour) && $ClientHour >= 0 && $ClientHour < 24) {
         $HourOffset = $ClientHour - date('G', time());
      
         if (Gdn::Session()->IsValid() && Gdn::Session()->ValidateTransientKey($TransientKey)) {
            Gdn::UserModel()->SetField(Gdn::Session()->UserID, 'HourOffset', $HourOffset);
            $Success = TRUE;
         }
      }
         
      $this->Render();
   }
   
   public function SetHourOffset() {
      $Form = new Gdn_Form();
      
      if ($Form->AuthenticatedPostBack()) {
         if (!Gdn::Session()->IsValid()) {
            throw PermissionException('Garden.SignIn.Allow');
         }
         
         $HourOffset = $Form->GetFormValue('HourOffset');
         Gdn::UserModel()->SetField(Gdn::Session()->UserID, 'HourOffset', $HourOffset);
         
         $this->SetData('Result', TRUE);
         $this->SetData('HourOffset', $HourOffset);
         
         $time = time();
         $this->SetData('UTCDateTime', gmdate('r', $time));
         $this->SetData('UserDateTime', gmdate('r', $time + $HourOffset * 3600));
      } else {
         throw ForbiddenException('GET');
      }
      
      $this->Render('Blank');
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
   
   /** 
    * Return some meta information about any page on the internet in JSON format.
    */
   public function FetchPageInfo($Url = '') {
      $PageInfo = FetchPageInfo($Url);
      $this->SetData('PageInfo', $PageInfo);
      $this->MasterView = 'default';
      $this->RemoveCssFile('admin.css');
      $this->AddCssFile('style.css');
      $this->SetData('_NoPanel', TRUE);
      $this->Render();
   }   
}
