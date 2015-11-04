<?php
/**
 * Perform miscellaneous operations for Dashboard.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handles /utility endpoint.
 */
class UtilityController extends DashboardController {

    /** @var array Models to automatically instantiate. */
    public $Uses = array('Form');

    /** @var  Gdn_Form $Form */
    public $Form;

    /**
     * @var array Special-case HTTP headers that are otherwise unidentifiable as HTTP headers.
     * Typically, HTTP headers in the $_SERVER array will be prefixed with
     * `HTTP_` or `X_`. These are not so we list them here for later reference.
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
    public function css($ThemeType, $Filename) {
        $AssetModel = new AssetModel();
        $AssetModel->ServeCss($ThemeType, $Filename);
    }

    /**
     * Runs before every call to this controller.
     */
    public function initialize() {
        parent::initialize();
        Gdn_Theme::section('Dashboard');
        set_time_limit(0); // Is this even doing anything?
    }

//   /**
//    * Call a method on the given model.
//    */
//   public function model() {
//      $this->permission('Garden.Settings.Manage');
//
//      $this->deliveryMethod(DELIVERY_METHOD_JSON);
//      $this->deliveryType(DELIVERY_TYPE_DATA);
//
//      $Args = func_get_args();
//
//      // Check to see if we have a model.
//      $ModelName = StringEndsWith(array_shift($Args), 'Model', TRUE, true);
//      $ModelName = ucfirst($ModelName).'Model';
//      if (!class_exists($ModelName)) {
//         throw notFoundException($ModelName);
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
//               $this->deliveryMethod(DELIVERY_METHOD_XML);
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
//      if ($this->Request->get('help')) {
//         $this->setData('Model', get_class($Model));
//         if ($MethodName) {
//            if (!method_exists($Model, $MethodName)) {
//               throw notFoundException($ModelName.'->'.$MethodName.'()');
//            }
//            $this->setData('Method', $MethodName);
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
//            $this->setData('Args', $Args);
//         } else {
//            $Class = new ReflectionClass($Model);
//            $Meths = $Class->getMethods();
//            $Methods = array();
//            foreach ($Meths as $Meth) {
//               $MethodName = $Meth->getName();
//               if (stringBeginsWith($MethodName, '_'))
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
//            $this->setData('Methods', $Methods);
//         }
//      } else {
//         if (!method_exists($Model, $MethodName)) {
//            throw notFoundException($ModelName.'->'.$MethodName.'()');
//         }
//
//         $MethodArgs = ReflectArgs($Callback, $this->Request->get(), $Args);
//
//         $Result = call_user_func_array($Callback, $MethodArgs);
//
//         if (is_array($Result))
//            $this->Data = $Result;
//         elseif (is_a($Result, 'Gdn_DataSet')) {
//            $Result = $Result->resultArray();
//            $this->Data = $Result;
//         } elseif (is_a($Result, 'stdClass'))
//            $this->Data = (array)$Result;
//         else
//            $this->setData('Result', $Result);
//      }
//
//      $this->render();
//   }

    /**
     * Redirect to another page.
     * @since 2.0.18b4
     */
//   public function redirect() {
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
//         $Url = url('/', true);
//      }
//
//      $Get = $this->Request->get();
//      if (count($Get) > 0) {
//         $Query = '?'.http_build_query($Get);
//      } else {
//         $Query = '';
//      }
//
//      redirect($Url.$Query);
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
    public function sort() {
        $this->permission('Garden.Settings.Manage');

        if (Gdn::request()->isAuthenticatedPostBack()) {
            $TableID = Gdn::request()->Post('TableID');
            if ($TableID) {
                $Rows = Gdn::request()->Post($TableID);
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
                            list(, $ID) = explode('_', $ID, 2);
                        }
                        if (!$ID) {
                            continue;
                        }

                        $TableModel->setField($ID, 'Sort', $Sort);
                    }
                    $this->setData('Result', true);
                }
            }
        }

        $this->render('Blank');
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
    public function set($UserPropertyColumn = '', $Name = '', $Value = '', $TransientKey = '') {
        $this->_DeliveryType = DELIVERY_TYPE_BOOL;
        $Session = Gdn::session();
        $Success = false;
        if (in_array($UserPropertyColumn, array('preference', 'attribute'))
            && $Name != ''
            && $Value != ''
            && $Session->UserID > 0
            && $Session->validateTransientKey($TransientKey)
        ) {
            $UserModel = Gdn::factory("UserModel");
            $Method = $UserPropertyColumn == 'preference' ? 'SavePreference' : 'SaveAttribute';
            $Success = $UserModel->$Method($Session->UserID, $Name, $Value) ? 'TRUE' : 'FALSE';
        }

        if (!$Success) {
            $this->Form->addError('ErrorBool');
        }

        // Redirect back where the user came from if necessary
        if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
            redirect($_SERVER['HTTP_REFERER']);
        } else {
            $this->render();
        }
    }

    public function sprites() {
        $this->removeCssFile('admin.css');
        $this->addCssFile('style.css');
        $this->addCssFile('vanillicon.css', 'static');
        $this->MasterView = 'default';

        $this->CssClass = 'SplashMessage NoPanel';
        $this->setData('_NoMessages', true);
        $this->setData('Title', 'Sprite Sheet');
        $this->render();
    }

    /**
     * Update database structure based on current definitions in each app's structure.php file.
     *
     * @since 2.0.?
     * @access public
     * @param string $appName Unique app name or 'all' (default).
     */
    public function structure($appName = 'all') {
        $this->permission('Garden.Settings.Manage');

        if (!$this->Form->authenticatedPostBack()) {
            // The form requires a postback to do anything.
            $step = 'start';
        } else {
            $scan = $this->Form->getFormValue('Scan');
            $run = $this->Form->getFormValue('Run');
            $step = 'start';
            if (!empty($scan)) {
                $step = 'scan';
            } elseif (!empty($run)) {
                $step = 'run';
            }
        }

        switch ($step) {
            case 'scan':
                $this->runStructure($appName, true);
                break;
            case 'run':
                $this->runStructure($appName, false);
                break;
            case 'start';
            default:
                // Nothing to do here.
        }

        $this->setData('Step', $step);
        $this->addSideMenu('dashboard/settings/configure');
        $this->addCssFile('admin.css');
        $this->setData('Title', t('Database Structure Upgrades'));
        $this->render();
    }

    /**
     * Run the database structure or /utility/structure.
     *
     * Note: Keep this method protected!
     *
     * @param string $appName Unique app name or 'all' (default).
     * @param bool $captureOnly Whether to list changes rather than execute (0 or 1).
     * @throws Exception
     */
    protected function runStructure($appName = 'all', $captureOnly = true) {
        // This permission is run again to be sure someone doesn't accidentally call this method incorrectly.
        $this->permission('Garden.Settings.Manage');

        $Files = array();
        $appName = $appName == '' ? 'all' : $appName;
        if ($appName == 'all') {
            // Load all application structure files.
            $ApplicationManager = new Gdn_ApplicationManager();
            $Apps = $ApplicationManager->enabledApplications();
            $AppNames = array_column($Apps, 'Folder');
            foreach ($AppNames as $appName) {
                $Files[] = combinePaths(array(PATH_APPLICATIONS, $appName, 'settings', 'structure.php'), DS);
            }
            $appName = 'all';
        } else {
            // Load that specific application structure file.
            $Files[] = combinePaths(array(PATH_APPLICATIONS, $appName, 'settings', 'structure.php'), DS);
        }
        $Drop = false;
        $Explicit = false;
        $captureOnly = !($captureOnly == '0');
        $Structure = Gdn::structure();
        $Structure->CaptureOnly = $captureOnly;
        $SQL = Gdn::sql();
        $SQL->CaptureModifications = $captureOnly;
        $this->setData('CaptureOnly', $Structure->CaptureOnly);
        $this->setData('Drop', $Drop);
        $this->setData('Explicit', $Explicit);
        $this->setData('ApplicationName', $appName);
        $this->setData('Status', '');
        $FoundStructureFile = false;
        foreach ($Files as $File) {
            if (file_exists($File)) {
                $FoundStructureFile = true;
                try {
                    include($File);
                } catch (Exception $Ex) {
                    $this->Form->addError($Ex);
                }
            }
        }

        // Run the structure of all of the plugins.
        $Plugins = Gdn::pluginManager()->enabledPlugins();
        foreach ($Plugins as $PluginKey => $Plugin) {
            $PluginInstance = Gdn::pluginManager()->getPluginInstance($PluginKey, Gdn_PluginManager::ACCESS_PLUGINNAME);
            if (method_exists($PluginInstance, 'Structure')) {
                $PluginInstance->structure();
            }
        }

        if (property_exists($Structure->Database, 'CapturedSql')) {
            $this->setData('CapturedSql', (array)$Structure->Database->CapturedSql);
        } else {
            $this->setData('CapturedSql', array());
        }

        if ($this->Form->errorCount() == 0 && !$captureOnly && $FoundStructureFile) {
            $this->setData('Status', 'The structure was successfully executed.');
        }
    }

    /**
     * Run a structure update on the database.
     *
     * @since 2.0.?
     * @access public
     */
    public function update() {
        try {
            // Check for permission or flood control.
            // These settings are loaded/saved to the database because we don't want the config file storing non/config information.
            $Now = time();
            $LastTime = Gdn::get('Garden.Update.LastTimestamp', 0);

            if ($LastTime + (60 * 60 * 24) > $Now) {
                // Check for flood control.
                $Count = Gdn::get('Garden.Update.Count', 0) + 1;
                if ($Count > 5) {
                    if (!Gdn::session()->checkPermission('Garden.Settings.Manage')) {
                        // We are only allowing an update of 5 times every 24 hours.
                        throw permissionException();
                    }
                }
            } else {
                $Count = 1;
            }
            Gdn::set('Garden.Update.LastTimestamp', $Now);
            Gdn::set('Garden.Update.Count', $Count);
        } catch (PermissionException $Ex) {
            return;
        } catch (Exception $Ex) {
        }

        try {
            // Run the structure.
            $UpdateModel = new UpdateModel();
            $UpdateModel->runStructure();
            $this->setData('Success', true);
        } catch (Exception $Ex) {
            $this->setData('Success', false);
            if (Debug()) {
                throw $Ex;
            }
        }

        if (Gdn::session()->checkPermission('Garden.Settings.Manage')) {
            saveToConfig('Garden.Version', APPLICATION_VERSION);
        }

        if ($Target = $this->Request->get('Target')) {
            redirect($Target);
        }

        $this->fireEvent('AfterUpdate');

        $this->MasterView = 'empty';
        $this->CssClass = 'Home';
        $this->render();
    }

    /**
     * Because people try this a lot and get confused.
     *
     * @since 2.0.18
     * @access public
     */
    public function upgrade() {
        $this->update();
    }

    /**
     * Signs of life.
     *
     * @since 2.0.?
     * @access public
     */
    public function alive() {
        $this->setData('Success', true);
        $this->MasterView = 'empty';
        $this->CssClass = 'Home';

        $this->fireEvent('Alive');

        $this->render();
    }

    /**
     *
     *
     * @throws Exception
     */
    public function ping() {
        $start = microtime(true);

        $this->setData('pong', true);
        $this->MasterView = 'empty';
        $this->CssClass = 'Home';

        $valid = true;

        // Test the cache.
        if (Gdn::cache()->activeEnabled()) {
            $k = betterRandomString(20);
            Gdn::cache()->store($k, 1);
            Gdn::cache()->increment($k, 1);
            $v = Gdn::cache()->get($k);

            if ($v !== 2) {
                $valid = false;
                $this->setData('cache', false);
            } else {
                $this->setData('cache', true);
            }

        } else {
            $this->setData('cache', 'disabled');
        }

        // Test the db.
        try {
            $users = Gdn::sql()->get('User', 'UserID', 'asc', 1);
            $this->setData('database', true);
        } catch (Exception $ex) {
            $this->setData('database', false);
            $valid = false;
        }

        $this->EventArguments['Valid'] =& $valid;
        $this->fireEvent('Ping');

        if (!$valid) {
            $this->statusCode(500);
        }

        $time = microtime(true) - $start;
        $this->setData('time', Gdn_Format::timespan($time));
        $this->setData('time_s', $time);
        $this->setData('valid', $valid);
        $this->title('Ping');

        $this->render();
    }

    /**
     * Set the user's timezone (hour offset).
     *
     * @since 2.0.0
     * @access public
     * @param string $ClientDate Client-reported datetime.
     * @param string $TransientKey Security token.
     */
    public function setClientHour($ClientHour = '', $TransientKey = '') {
        $this->_DeliveryType = DELIVERY_TYPE_BOOL;
        $Success = false;

        if (is_numeric($ClientHour) && $ClientHour >= 0 && $ClientHour < 24) {
            $HourOffset = $ClientHour - date('G', time());

            if (Gdn::session()->isValid() && Gdn::session()->validateTransientKey($TransientKey)) {
                Gdn::userModel()->setField(Gdn::session()->UserID, 'HourOffset', $HourOffset);
                $Success = true;
            }
        }

        $this->render();
    }

    /**
     *
     *
     * @throws Exception
     */
    public function setHourOffset() {
        $Form = new Gdn_Form();

        if ($Form->authenticatedPostBack()) {
            if (!Gdn::session()->isValid()) {
                throw permissionException('Garden.SignIn.Allow');
            }

            $HourOffset = $Form->getFormValue('HourOffset');
            Gdn::userModel()->setField(Gdn::session()->UserID, 'HourOffset', $HourOffset);

            $this->setData('Result', true);
            $this->setData('HourOffset', $HourOffset);

            $time = time();
            $this->setData('UTCDateTime', gmdate('r', $time));
            $this->setData('UserDateTime', gmdate('r', $time + $HourOffset * 3600));
        } else {
            throw forbiddenException('GET');
        }

        $this->render('Blank');
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
    public function getFeed($type = 'news', $length = 5, $feedFormat = 'normal') {
        $validTypes = array(
            'releases',
            'help',
            'news',
            'cloud'
        );
        $validFormats = array(
            'extended',
            'normal'
        );

        $length = is_numeric($length) && $length <= 50 ? $length : 5;

        if (!in_array($type, $validTypes)) {
            $type = 'news';
        }

        if (!in_array($feedFormat, $validFormats)) {
            $feedFormat = 'normal';
        }

        echo file_get_contents("http://vanillaforums.org/vforg/home/getfeed/{$type}/{$length}/{$feedFormat}/?DeliveryType=VIEW");
        $this->deliveryType(DELIVERY_TYPE_NONE);
        $this->render();
    }

    /**
     * Return some meta information about any page on the internet in JSON format.
     */
    public function fetchPageInfo($Url = '') {
        $PageInfo = fetchPageInfo($Url);

        if (!empty($PageInfo['Exception'])) {
            throw new Gdn_UserException($PageInfo['Exception'], 400);
        }

        $this->setData('PageInfo', $PageInfo);
        $this->MasterView = 'default';
        $this->removeCssFile('admin.css');
        $this->addCssFile('style.css');
        $this->addCssFile('vanillicon.css', 'static');

        $this->setData('_NoPanel', true);
        $this->render();
    }
}
