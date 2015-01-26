<?php if (!defined('APPLICATION')) exit();

set_time_limit(0);

/**
 * Perform miscellaneous operations for Dashboard.
 *
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

class UtilityController extends DashboardController {
   /** @var array Models to automatically instantiate. */
   public $Uses = array('Form');

   /**
    * Special-case HTTP headers that are otherwise unidentifiable as HTTP headers.
    * Typically, HTTP headers in the $_SERVER array will be prefixed with
    * `HTTP_` or `X_`. These are not so we list them here for later reference.
    *
    * @var array
    */
   protected static $specialHeaders = array(
      'CONTENT_TYPE',
      'CONTENT_LENGTH',
      'PHP_AUTH_USER',
      'PHP_AUTH_PW',
      'PHP_AUTH_DIGEST',
      'AUTH_TYPE'
   );

   /**
    * Gather all of the global styles together.
    * @param string ThemeType Either `desktop` or `mobile`.
    * @param string $Filename The basename of the file to
    * @since 2.1
    */
   public function Css($ThemeType, $Filename) {
      $AssetModel = new AssetModel();
      $AssetModel->ServeCss($ThemeType, $Filename);
   }

   public function Initialize() {
      parent::Initialize();
      Gdn_Theme::Section('Dashboard');
   }

   public function Rack() {
      safeHeader('Content-Type: application/json; charset=utf-8');
      date_default_timezone_set('America/Montreal');

      $keys = array('REQUEST_METHOD', 'SCRIPT_NAME', 'PATH_INFO', 'SERVER_NAME', 'SERVER_PORT', 'HTTP_ACCEPT', 'HTTP_ACCEPT_LANGUAGE', 'HTTP_ACCEPT_CHARSET', 'HTTP_USER_AGENT', 'HTTP_REMOTE_ADDR');
      $rack = array_intersect_key($_SERVER, array_fill_keys($keys, true));
      ksort($rack);

      // Extract the headers from $_SERVER.
      $headers = array();
      foreach ($_SERVER as $key => $value) {
         $key = strtoupper($key);
         if (strpos($key, 'X_') === 0 || strpos($key, 'HTTP_') === 0 || in_array($key, static::$specialHeaders)) {
            if ($key === 'HTTP_CONTENT_TYPE' || $key === 'HTTP_CONTENT_LENGTH') {
               continue;
            }
            $headers[$key] = $value;
         }
      }
      ksort($headers);

      $result = array(
         'rack' => $rack,
         'headers' => $headers,
         'get' => $_GET,
         'cookie' => $_COOKIE,
         'mobile' => array(
            'userAgentType' => userAgentType(),
            'isMobile' => IsMobile()
         )
      );

      echo json_encode($result);
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

      if (Gdn::Request()->IsAuthenticatedPostBack()) {
         $TableID = Gdn::Request()->Post('TableID');
         if ($TableID) {
            $Rows = Gdn::Request()->Post($TableID);
            if (is_array($Rows)) {
               $Table = str_replace(array('Table', '`'), '', $TableID);
               $ModelName = $Table.'Model';
               if (class_exists($ModelName)) {
                  $TableModel = new $ModelName();
               } else {
                  $TableModel = new Gdn_Model($Table);
               }

               foreach ($Rows as $Sort => $ID) {
                  if (strpos($ID, '_') !== false) {
                     list(,$ID) = explode('_', $ID, 2);
                  }
                  if (!$ID) {
                     continue;
                  }

                  $TableModel->SetField($ID, 'Sort', $Sort);
               }
               $this->SetData('Result', true);
            }
         }
      }

      $this->Render('Blank');
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
      $this->AddCssFile('vanillicon.css', 'static');
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
         if (Debug())
            throw $Ex;
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

   public function Ping() {
      $start = microtime(true);

      $this->SetData('pong', TRUE);
      $this->MasterView = 'empty';
      $this->CssClass = 'Home';

      $valid = true;

      // Test the cache.
      if (Gdn::Cache()->ActiveEnabled()) {
         $k = BetterRandomString(20);
         Gdn::Cache()->Store($k, 1);
         Gdn::Cache()->Increment($k, 1);
         $v = Gdn::Cache()->Get($k);

         if ($v !== 2) {
            $valid = false;
            $this->SetData('cache', false);
         } else {
            $this->SetData('cache', true);
         }

      } else {
         $this->SetData('cache', 'disabled');
      }

      // Test the db.
      try {
         $users = Gdn::SQL()->Get('User', 'UserID', 'asc', 1);
         $this->SetData('database', true);
      } catch(Exception $ex) {
         $this->SetData('database', false);
         $valid = false;
      }

      $this->EventArguments['Valid'] =& $valid;
      $this->FireEvent('Ping');

      if (!$valid) {
         $this->StatusCode(500);
      }

      $time = microtime(true) - $start;
      $this->SetData('time', Gdn_Format::Timespan($time));
      $this->SetData('time_s', $time);
      $this->SetData('valid', $valid);
      $this->Title('Ping');

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
      $this->AddCssFile('vanillicon.css', 'static');

      $this->SetData('_NoPanel', TRUE);
      $this->Render();
   }
}
