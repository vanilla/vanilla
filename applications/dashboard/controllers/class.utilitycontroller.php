<?php
/**
 * Perform miscellaneous operations for Dashboard.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handles /utility endpoint.
 */
class UtilityController extends DashboardController {

    /** A flag used to indicate the site was put into maintenance mode in an automated fashion. */
    const MAINTENANCE_AUTO = 2;

    /** @var array Models to automatically instantiate. */
    public $Uses = ['Form'];

    /** @var  Gdn_Form $Form */
    public $Form;

    /**
     * @var array Special-case HTTP headers that are otherwise unidentifiable as HTTP headers.
     * Typically, HTTP headers in the $_SERVER array will be prefixed with
     * `HTTP_` or `X_`. These are not so we list them here for later reference.
     */
    protected static $specialHeaders = [
        'CONTENT_TYPE',
        'CONTENT_LENGTH',
        'PHP_AUTH_USER',
        'PHP_AUTH_PW',
        'PHP_AUTH_DIGEST',
        'AUTH_TYPE'
    ];

    /**
     * Runs before every call to this controller.
     */
    public function initialize() {
        parent::initialize();
        set_time_limit(0); // Is this even doing anything?
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
    public function sort() {
        $this->permission('Garden.Settings.Manage');

        if (Gdn::request()->isAuthenticatedPostBack()) {
            $tableID = Gdn::request()->post('TableID');
            if ($tableID) {
                if (!preg_match('/^[A-Za-z0-9_]+$/', $tableID)) {
                    throw new InvalidArgumentException('Invalid TableID.');
                }
                $rows = Gdn::request()->post($tableID);
                if (is_array($rows)) {
                    $table = str_replace(['Table', '`'], '', $tableID);
                    $modelName = $table.'Model';
                    if (class_exists($modelName)) {
                        $tableModel = new $modelName();
                    } else {
                        $tableModel = new Gdn_Model($table);
                    }

                    foreach ($rows as $sort => $iD) {
                        if (strpos($iD, '_') !== false) {
                            list(, $iD) = explode('_', $iD, 2);
                        }
                        if (!$iD) {
                            continue;
                        }

                        $tableModel->setField($iD, 'Sort', $sort);
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
     * saves to the row of the user id performing this action (ie. $session->UserID). The
     * type of property column being saved should be specified in the url:
     * i.e. /dashboard/utility/set/preference/name/value/transientKey
     * or /dashboard/utility/set/attribute/name/value/transientKey
     *
     * @since 2.0.0
     * @access public
     * @param string $userPropertyColumn The type of value being saved: preference or attribute.
     * @param string $name The name of the property being saved.
     * @param string $value The value of the property being saved.
     * @param string $transientKey A unique transient key to authenticate that the user intended to perform this action.
     */
    public function set($userPropertyColumn = '', $name = '', $value = '', $transientKey = '') {
        deprecated('set', '', 'February 2017');

        $whiteList = [];

        if (c('Garden.Profile.ShowActivities', true)) {
            $whiteList = array_merge($whiteList, [
                'Email.WallComment',
                'Email.ActivityComment',
                'Popup.WallComment',
                'Popup.ActivityComment'
            ]);
        }

        $this->_DeliveryType = DELIVERY_TYPE_BOOL;
        $session = Gdn::session();
        $success = false;

        // Get index of whitelisted name
        $index = array_search(strtolower($name), array_map('strtolower', $whiteList));

        if (!empty($whiteList) && $index !== false) {

            // Force name to have casing present in whitelist
            $name = $whiteList[$index];

            // Force value
            if ($value != '1') {
                $value = '0';
            }

            if (in_array($userPropertyColumn, ['preference', 'attribute'])
                && $name != ''
                && $value != ''
                && $session->UserID > 0
                && $session->validateTransientKey($transientKey)
            ) {
                $userModel = Gdn::factory("UserModel");
                $method = $userPropertyColumn == 'preference' ? 'SavePreference' : 'SaveAttribute';
                $success = $userModel->$method($session->UserID, $name, $value) ? 'TRUE' : 'FALSE';
            }
        }

        if (!$success) {
            $this->Form->addError('ErrorBool');
        }

        // Redirect back where the user came from if necessary
        if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
            redirectTo($_SERVER['HTTP_REFERER']);
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
     */
    public function structure() {
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
                $this->runStructure(true);
                break;
            case 'run':
                $this->runStructure(false);
                break;
            case 'start':
            default:
                // Nothing to do here.
        }

        $this->setData('Step', $step);
        $this->setHighlightRoute('dashboard/settings/configure');
        $this->addCssFile('admin.css');
        $this->setData('Title', t('Database Structure Upgrades'));
        $this->render();
    }

    /**
     * Run the database structure or /utility/structure.
     *
     * Note: Keep this method private!
     *
     * @param bool $captureOnly Whether to list changes rather than execute.
     * @throws Exception Throws an exception if there was an error in the structure process.
     */
    private function runStructure($captureOnly = true) {
        // This permission is run again to be sure someone doesn't accidentally call this method incorrectly.
        $this->permission('Garden.Settings.Manage');

        $updateModel = new UpdateModel();
        $capturedSql = $updateModel->runStructure($captureOnly);
        $this->setData('CapturedSql', $capturedSql);

        $issues = Gdn::structure()->getIssues();
        if ($this->Form->errorCount() == 0 && !$captureOnly) {
            if (empty($issues)) {
                $this->setData('Status', 'The structure was successfully executed.');
            } else {
                $this->setData('Status', 'The structure completed with issues.');
            }
        }
        $this->setData('Issues', $issues);
    }

    /**
     * Run a structure update on the database.
     *
     * It should always be possible to call this method, even if no database tables exist yet.
     * A working forum database should be built from scratch where none exists. Therefore,
     * it can have no reliance on existing data calls, or they must be able to fail gracefully.
     *
     * @since 2.0.?
     * @access public
     */
    public function update() {
        // Check for permission or flood control.
        // These settings are loaded/saved to the database because we don't want the config file storing non/config information.
        $now = time();
        $lastTime = 0;
        $count = 0;

        try {
            $lastTime = Gdn::get('Garden.Update.LastTimestamp', 0);
        } catch (Exception $ex) {
            // We don't have a GDN_UserMeta table yet. Sit quietly and one will appear.
        }

        if ($lastTime + (60 * 60 * 24) > $now) {
            // Check for flood control.
            try {
                $count = Gdn::get('Garden.Update.Count', 0) + 1;
            } catch (Exception $ex) {
                // Once more we sit, watching the breath.
            }
            if ($count > 5) {
                if (!Gdn::session()->checkPermission('Garden.Settings.Manage')) {
                    // We are only allowing an update of 5 times every 24 hours.
                    throw permissionException();
                }
            }
        } else {
            $count = 1;
        }

        try {
            Gdn::set('Garden.Update.LastTimestamp', $now);
            Gdn::set('Garden.Update.Count', $count);
        } catch (Exception $ex) {
            // What is a GDN_UserMeta table, really? Suffering.
        }

        try {
            // Run the structure.
            $updateModel = new UpdateModel();
            $updateModel->runStructure();
            $this->setData('Success', true);
        } catch (Exception $ex) {
            $this->setData('Success', false);
            $this->setData('Error', $ex->getMessage());
            if (debug()) {
                throw $ex;
            }
        }

        if (Gdn::session()->checkPermission('Garden.Settings.Manage')) {
            saveToConfig('Garden.Version', APPLICATION_VERSION);
        }

        if ($target = $this->Request->get('Target')) {
            redirectTo($target);
        }

        $this->fireEvent('AfterUpdate');

        if ($this->deliveryType() === DELIVERY_TYPE_DATA) {
            // Make sure that we do not disclose anything too sensitive here!
            $this->Data = array_filter($this->Data, function($key) {
                return in_array(strtolower($key), ['success', 'error']);
            }, ARRAY_FILTER_USE_KEY);
        }

        $this->MasterView = 'empty';
        $this->CssClass = 'Home';
        Gdn_Theme::section('Utility');
        $this->render('update', 'utility', 'dashboard');
    }

    /**
     * Loads the files from resources/deletedfiles.txt into an array and returns it.
     * Returns null if the deletedfiles.txt file is not found.
     *
     * @return array|null
     */
    private function loadDeleted() {
        $deletedFilesPath = PATH_ROOT.'/resources/upgrade/deletedfiles.txt';
        $deletedFiles = file($deletedFilesPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return $deletedFiles;
    }


    /**
     * Checks if any deleted files exist in the vanilla file structure. Saves an array of the existing deleted files
     * to the data array.
     */
    private function checkDeleted() {
        $deletedFiles = $this->loadDeleted();
        $okFiles = ['.htaccess'];

        $existingFiles = [];
        if ($deletedFiles !== null) {
            foreach ($deletedFiles as $file) {
                if (file_exists(PATH_ROOT.DS.$file) && !in_array($file, $okFiles)) {
                    $file = htmlspecialchars($file);
                    $existingFiles[] = $file;
                }
            }
            $this->setData('DeletedFiles', $existingFiles);
        }
    }

    /**
     * A special endpoint for users upgrading their Vanilla installation.
     * Adds a special check for deleted files that may still exist post-upgrade.
     *
     * @since 2.0.18
     * @access public
     */
    public function upgrade() {
        $this->permission('Garden.Settings.Manage');
        $this->checkDeleted();
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
        Gdn_Theme::section('Utility');
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
        Gdn_Theme::section('Utility');

        $this->render();
    }

    /**
     * Set the user's timezone (hour offset).
     *
     * @since 2.0.0
     * @access public
     * @param string $ClientDate Client-reported datetime.
     * @param string $transientKey Security token.
     */
    public function setClientHour($clientHour = '', $transientKey = '') {
        $this->_DeliveryType = DELIVERY_TYPE_BOOL;
        $success = false;

        if (is_numeric($clientHour) && $clientHour >= 0 && $clientHour < 24) {
            $hourOffset = $clientHour - date('G', time());

            if (Gdn::session()->isValid() && Gdn::session()->validateTransientKey($transientKey)) {
                Gdn::userModel()->setField(Gdn::session()->UserID, 'HourOffset', $hourOffset);
                $success = true;
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
        $form = new Gdn_Form();

        if ($form->authenticatedPostBack()) {
            if (!Gdn::session()->isValid()) {
                throw permissionException('Garden.SignIn.Allow');
            }

            $hourOffset = $form->getFormValue('HourOffset');
            Gdn::userModel()->setField(Gdn::session()->UserID, 'HourOffset', $hourOffset);

            // If we receive a time zone, only accept it if we can verify it as a valid identifier.
            $timeZone = $form->getFormValue('TimeZone');
            if (!empty($timeZone)) {
                try {
                    $tz = new DateTimeZone($timeZone);
                    Gdn::userModel()->saveAttribute(
                        Gdn::session()->UserID,
                        ['TimeZone' => $tz->getName(), 'SetTimeZone' => null]
                    );
                } catch (\Exception $ex) {
                    Logger::log(Logger::ERROR, $ex->getMessage(), ['timeZone' => $timeZone]);

                    Gdn::userModel()->saveAttribute(
                        Gdn::session()->UserID,
                        ['TimeZone' => null, 'SetTimeZone' => $timeZone]
                    );
                    $timeZone = '';
                }
            } elseif ($currentTimeZone = Gdn::session()->getAttribute('TimeZone')) {
                // Check to see if the current timezone agrees with the posted offset.
                try {
                    $tz = new DateTimeZone($currentTimeZone);
                    $currentHourOffset = $tz->getOffset(new DateTime()) / 3600;
                    if ($currentHourOffset != $hourOffset) {
                        // Clear out the current timezone or else it will override the browser's offset.
                        Gdn::userModel()->saveAttribute(
                            Gdn::session()->UserID,
                            ['TimeZone' => null, 'SetTimeZone' => null]
                        );
                    } else {
                        $timeZone = $tz->getName();
                    }
                } catch (Exception $ex) {
                    Logger::log(Logger::ERROR, "Clearing out bad timezone: {timeZone}", ['timeZone' => $currentTimeZone]);
                    // Clear out the bad timezone.
                    Gdn::userModel()->saveAttribute(
                        Gdn::session()->UserID,
                        ['TimeZone' => null, 'SetTimeZone' => null]
                    );
                }
            }

            $this->setData('Result', true);
            $this->setData('HourOffset', $hourOffset);
            $this->setData('TimeZone', $timeZone);

            $time = time();
            $this->setData('UTCDateTime', gmdate('r', $time));
            $this->setData('UserDateTime', gmdate('r', $time + $hourOffset * 3600));
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
        $validTypes = [
            'releases',
            'help',
            'news',
            'cloud'
        ];
        $validFormats = [
            'extended',
            'normal'
        ];

        $length = is_numeric($length) && $length <= 50 ? $length : 5;

        if (!in_array($type, $validTypes)) {
            $type = 'news';
        }

        if (!in_array($feedFormat, $validFormats)) {
            $feedFormat = 'normal';
        }

        echo file_get_contents("https://open.vanillaforums.com/vforg/home/getfeed/{$type}/{$length}/{$feedFormat}/?DeliveryType=VIEW");
        $this->deliveryType(DELIVERY_TYPE_NONE);
        $this->render();
    }

    /**
     * Return some meta information about any page on the internet in JSON format.
     */
    public function fetchPageInfo($url = '') {
        $pageInfo = fetchPageInfo($url);

        if (!empty($pageInfo['Exception'])) {
            throw new Gdn_UserException($pageInfo['Exception'], 400);
        }

        $this->setData('PageInfo', $pageInfo);
        $this->MasterView = 'default';
        $this->removeCssFile('admin.css');
        $this->addCssFile('style.css');
        $this->addCssFile('vanillicon.css', 'static');

        $this->setData('_NoPanel', true);
        $this->render();
    }

    /**
     * Toggle whether or not the site is in maintenance mode.
     *
     * @param int $updateMode
     */
    public function maintenance($updateMode = 0) {
        if (!$this->Form->authenticatedPostBack(true)) {
            throw forbiddenException('GET');
        }
        $this->permission('Garden.Settings.Manage');
        $currentMode = c('Garden.UpdateMode');

        /**
         * If $updateMode is equal to self::MAINTENANCE_AUTO, it assumed this action was performed via an automated
         * process.  A bit flag is added to the current value, so the original setting is restored, once maintenance
         * mode is disabled through this endpoint.
         */
        if ($updateMode == self::MAINTENANCE_AUTO) {
            // Apply the is-auto flag to the current maintenance setting, so the original setting can be retrieved.
            $updateMode = ($currentMode | $updateMode);
        } elseif ($updateMode == 0 && ($currentMode & self::MAINTENANCE_AUTO)) {
            // If the is-auto flag is set, restore the original UpdateMode value.
            $updateMode = ($currentMode & ~self::MAINTENANCE_AUTO);
        } else {
            $updateMode = (bool)$updateMode;
        }

        // Save the new setting and output the result.
        saveToConfig('Garden.UpdateMode', $updateMode);
        $this->setData(['UpdateMode' => $updateMode]);
        $this->deliveryType(DELIVERY_TYPE_DATA);
        $this->deliveryMethod(DELIVERY_METHOD_JSON);
        $this->render();
    }

    /**
     * Redirect to touch icon.
     *
     * @since 1.0
     * @access public
     */
    public function showTouchIcon() {
        $icon = c('Garden.TouchIcon');

        if (!empty($icon)) {
            redirectTo(Gdn_Upload::url($icon), 302, false);
        } else {
            throw new Exception('Touch icon not found.', 404);
        }
    }
}
