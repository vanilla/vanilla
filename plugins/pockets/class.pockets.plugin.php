<?php
/**
 * @copyright 2008-2016 Vanilla Forums, Inc.
 * @license GNU GPLv2
 */

/**
 * Class PocketsPlugin
 */
class PocketsPlugin extends Gdn_Plugin {

    /** @var array Counters for the various locations. */
    protected $_Counters = [];

    /** @var array  */
    public $Locations = [
        'Content' => ['Name' => 'Content'],
        'Panel' => ['Name' => 'Panel'],
        'BetweenDiscussions' => ['Name' => 'Between Discussions', 'Wrap' => ['<li>', '</li>']],
        'BetweenComments' => ['Name' => 'Between Comments', 'Wrap' => ['<li>', '</li>']],
        'Head' => ['Name' => 'Head'],
        'Foot' => ['Name' => 'Foot'],
        'Custom' => ['Name' => 'Custom']
    ];

    /** @var array All of the pockets indexed by location. */
    protected $_Pockets = [];

    /** @var array  */
    protected $_PocketNames = [];

    /** @var bool  */
    protected $StateLoaded = false;

    /** Whether or not to display test items for all pockets. */
    public $ShowPocketLocations = null;

    /**
     * PocketsPlugin constructor.
     */
    public function __construct() {
        parent::__construct();

        // Switch our HTML wrapper when we're in a table view.
        if (c('Vanilla.Discussions.Layout') == 'table') {
            // Admin checks add a column.
            $useAdminChecks = c('Vanilla.AdminCheckboxes.Use') && Gdn::session()->checkPermission('Garden.Moderation.Manage');
            $colspan = c('Plugins.Pockets.Colspan', ($useAdminChecks) ? 6 : 5);
            $this->Locations['BetweenDiscussions']['Wrap'] = ['<tr><td colspan="'.$colspan.'">', '</td></tr>'];
        }
    }

    /**
     * Add test mode to every page.
     *
     * @param $sender
     */
    public function base_render_before($sender) {
        if ($this->ShowPocketLocations === null) {
            $this->ShowPocketLocations = c('Plugins.Pockets.ShowLocations');
        }
        if ($this->ShowPocketLocations && checkPermission('Plugins.Pockets.Manage') && $sender->MasterView != 'admin') {
            // Add the css for the test pockets to the page.
            $sender->addCSSFile('pockets.css', 'plugins/Pockets');
        }
    }

    /**
     * Adds "Media" menu option to the Forum menu on the dashboard.
     *
     * @param $sender
     */
    public function base_getAppSettingsMenuItems_handler($sender) {
        $menu = $sender->EventArguments['SideMenu'];
        $menu->addItem('Appearance', t('Appearance'));
        $menu->addLink('Appearance', t('Pockets'), 'settings/pockets', 'Plugins.Pockets.Manage');
    }

    /**
     *
     *
     * @param $sender
     */
    public function base_beforeRenderAsset_handler($sender) {
        $assetName = valr('EventArguments.AssetName', $sender);
        $this->processPockets($sender, $assetName, Pocket::REPEAT_BEFORE);
    }

    /**
     *
     *
     * @param $sender
     */
    public function base_afterRenderAsset_handler($sender) {
        $assetName = valr('EventArguments.AssetName', $sender);
        $this->processPockets($sender, $assetName, Pocket::REPEAT_AFTER);
    }

    /**
     *
     *
     * @param $sender
     */
    public function base_betweenRenderAsset_handler($sender) {
        $assetName = valr('EventArguments.AssetName', $sender);
        $this->processPockets($sender, $assetName);
    }

    /**
     *
     *
     * @param $sender
     */
    public function base_betweenDiscussion_handler($sender) {
        $this->processPockets($sender, 'BetweenDiscussions');
    }

    /**
     *
     *
     * @param $sender
     */
    public function base_beforeCommentDisplay_handler($sender) {
        // We don't want pockets to display before the first comment because they are only between comments.
        $processed = isset($this->_Counters['BeforeCommentDisplay']);
        if (!$processed) {
            $this->_Counters['BeforeCommentDisplay'] = true;
            return;
        }
        $this->processPockets($sender, 'BetweenComments');
    }

    /**
     * Main list for a pocket management.
     *
     * @param Gdn_Controller $sender.
     * @param array $args
     * @return mixed
     */
    public function settingsController_pockets_create($sender, $args = []) {
        $sender->permission('Plugins.Pockets.Manage');
        $sender->setHighlightRoute('settings/pockets');
        $sender->addJsFile('pockets.js', 'plugins/Pockets');

        $page = val(0, $args);
        switch(strtolower($page)) {
            case 'add':
                return $this->_add($sender);
                break;
            case 'edit':
                return $this->_edit($sender, val(1, $args));
                break;
            case 'delete':
                return $this->_delete($sender, val(1, $args));
                break;
            case 'enable':
                return $this->_enable($sender, val(1, $args));
                break;
            case 'disable':
                return $this->_disable($sender, val(1, $args));
                break;
            default:
                return $this->_index($sender, $args);
        }
    }

    /**
     *
     *
     * @param $sender
     * @param $args
     */
    protected function _index($sender, $args) {
        $sender->setData('Title', t('Pockets'));

        // Grab the pockets from the DB.
        $pocketData = Gdn::sql()
            ->get('Pocket', 'Location, `Sort`')
            ->resultArray();

        // Add notes to the pockets data.
        foreach ($pocketData as $index => &$pocketRow) {

            $mobileOnly = $pocketRow['MobileOnly'];
            $mobileNever = $pocketRow['MobileNever'];
            $noAds = $pocketRow['Type'] == Pocket::TYPE_AD;
            $testing = Pocket::inTestMode($pocketRow);
            $meta = [];

            if ($pocketRow['Repeat'] && $pocketRow['Repeat'] != Pocket::REPEAT_ONCE) {
                $pocketRow['Location'] .= " ({$pocketRow['Repeat']})";
            }

            if ($location = htmlspecialchars($pocketRow['Location'])) {
                $meta['location'] = [
                    'label' => t('Location'),
                    'value' => $location
                ];
            }
            if ($page = htmlspecialchars($pocketRow['Page'])) {
                $meta['page'] = [
                    'label' => t('Page'),
                    'value' => $page
                ];
            }
            if ($mobileNever && $mobileOnly) {
                $meta['visibility'] = [
                    'label' => t('Visibility'),
                    'value' => t('Hidden')
                ];
            } else if ($mobileOnly) {
                $meta['visibility'] = [
                    'label' => t('Visibility'),
                    'value' => t('Shown only on mobile')
                ];
            } else if ($mobileNever) {
                $meta['visibility'] = [
                    'label' => t('Visibility'),
                    'value' => t('Hidden for mobile')
                ];
            }
            if ($noAds) {
                $adsDesc = t('Users with the Garden.NoAds.Allow permission will not see this pocket.');
                $meta['visibility'] = [
                    'label' => t('This pocket is an ad'),
                    'value' => $adsDesc
                ];
            }
            if ($testing) {
                $testingDesc = t('Only visible to users with the Plugins.Pockets.Manage permission.');
                $meta['testmode'] = [
                    'label' => t('In test mode'),
                    'value' => $testingDesc
                ];
            }

            $pocketRow['Meta'] = $meta;
        }

        $sender->setData('PocketData', $pocketData);

        $form = new Gdn_Form();

        // Save global options.
        switch (val(0, $args)) {
            case 'showlocations':
                saveToConfig('Plugins.Pockets.ShowLocations', true);
                break;
            case 'hidelocations':
                saveToConfig('Plugins.Pockets.ShowLocations', false, ['RemoveEmpty' => true]);
                break;
        }

        $sender->Form = $form;
        $sender->render('Index', '', 'plugins/Pockets');
    }

    /**
     *
     *
     * @param $sender
     * @return mixed
     */
    protected function _add($sender) {
        $sender->setData('Title', sprintf(t('Add %s'), t('Pocket')));
        return $this->_addEdit($sender);
    }

    /**
     * Updates the Disabled field for a pocket. Informs the user and updates the HTML for a toggle with the id
     * `pockets-toggle-{pocketID}`. This is automatically added if using the `renderPocketToggle()` function to
     * render the toggle.
     *
     * @param Gdn_Controller $sender
     * @param string $pocketID The ID of the pocket to modify.
     * @param $disabledState Either Pocket::ENABLED or Pocket::DISABLED
     * @throws Exception
     * @throws Gdn_UserException
     */
    private function setDisabled($sender, $pocketID, $disabledState) {
        $sender->permission('Plugins.Pockets.Manage');

        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception('Requires POST', 405);
        }

        if (empty($pocketID)) {
            $sender->errorMessage('Must specify pocket ID.');
        }

        $values = [
            'PocketID' => $pocketID,
            'Disabled' => $disabledState
        ];

        $pocketModel = new Gdn_Model('Pocket');
        $pocketModel->save($values);

        $newToggle = renderPocketToggle($values);
        $sender->jsonTarget('#pockets-toggle-'.$pocketID, $newToggle);
        $sender->informMessage($disabledState === Pocket::DISABLED ? t('Pocket disabled.') : t('Pocket enabled.'));
        $sender->render('blank', 'utility', 'dashboard');
    }

    /**
     * Endpoint to disable a pocket.
     *
     * @param Gdn_Controller $sender
     * @param string $pocketID
     */
    public function _disable($sender, $pocketID = '') {
        $this->setDisabled($sender, $pocketID, Pocket::DISABLED);
    }

    /**
     * Endpoint to enable a pocket.
     *
     * @param Gdn_Controller $sender
     * @param string $pocketID
     */
    public function _enable($sender, $pocketID = '') {
        $this->setDisabled($sender, $pocketID, Pocket::ENABLED);
    }

    /**
     *
     *
     * @param SettingsController $sender
     * @param bool|false $pocketID
     * @return mixed
     * @throws Gdn_UserException
     */
    protected function _addEdit($sender, $pocketID = false) {
        $form = new Gdn_Form();
        $pocketModel = new Gdn_Model('Pocket');
        $form->setModel($pocketModel);
        $sender->ConditionModule = new ConditionModule($sender);
        $sender->Form = $form;

        if ($form->authenticatedPostBack()) {
            // Save the pocket.
            if ($pocketID !== false) {
                $form->setFormValue('PocketID', $pocketID);
            }

            // Convert the form data into a format digestable by the database.
            $repeat = $form->getFormValue('RepeatType');
            switch ($repeat) {
                case Pocket::REPEAT_EVERY:
                    $pocketModel->Validation->applyRule('EveryFrequency', 'Integer');
                    $pocketModel->Validation->applyRule('EveryBegin', 'Integer');
                    $frequency = $form->getFormValue('EveryFrequency', 1);
                    if (!$frequency || !validateInteger($frequency) || $frequency < 1)
                        $frequency = 1;
                    $repeat .= ' '.$frequency;
                    if ($form->getFormValue('EveryBegin', 1) > 1)
                        $repeat .= ','.$form->getFormValue('EveryBegin');
                    break;
                case Pocket::REPEAT_INDEX:
                    $pocketModel->Validation->addRule('IntegerArray', 'function:ValidateIntegerArray');
                    $pocketModel->Validation->applyRule('Indexes', 'IntegerArray');
                    $indexes = explode(',', $form->getFormValue('Indexes', ''));
                    $indexes = array_map('trim', $indexes);
                    $repeat .= ' '.implode(',', $indexes);
                    break;
                default:
                    break;
            }
            $form->setFormValue('Repeat', $repeat);
            $form->setFormValue('Sort', 0);
            $form->setFormValue('Format', 'Raw');
            $condition = Gdn_Condition::toString($sender->ConditionModule->conditions(true));
            $form->setFormValue('Condition', $condition);
            if ($form->getFormValue('Ad', 0)) {
                $form->setFormValue('Type', Pocket::TYPE_AD);
            } else {
                $form->setFormValue('Type', Pocket::TYPE_DEFAULT);
            }

            // Deprecating the 3-state Disabled field (enabled, disabled, testing) in favour of a separate 'TestMode'
            // field. All testing pockets should be enabled with the testing flag set.
            if ($form->getFormValue('Disabled') === Pocket::TESTING) {
                $form->setFormValue('Disabled', Pocket::ENABLED);
                // The 'TestMode' property will already be set to true in the UI, we'll let save() set it.
            }

            $enabled = $form->getFormValue('Enabled');
            $form->setFormValue('Disabled', $enabled === "1" ? Pocket::ENABLED : Pocket::DISABLED);

            $saved = $form->save();
            if ($saved) {
                $sender->StatusMessage = t('Your changes have been saved.');
                $sender->setRedirectTo('settings/pockets');
            }
        } else {
            if ($pocketID !== false) {
                // Load the pocket.
                $pocket = $pocketModel->getWhere(['PocketID' => $pocketID])->firstRow(DATASET_TYPE_ARRAY);
                if (!$pocket) {
                    return Gdn::dispatcher()->dispatch('Default404');
                }

                // Convert some of the pocket data into a format digestable by the form.
                list($repeatType, $repeatFrequency) = Pocket::parseRepeat($pocket['Repeat']);
                $pocket['RepeatType'] = $repeatType;
                $pocket['EveryFrequency'] = getValue(0, $repeatFrequency, 1);
                $pocket['EveryBegin'] = getValue(1, $repeatFrequency, 1);
                $pocket['Indexes'] = implode(',', $repeatFrequency);
                $pocket['Ad'] = $pocket['Type'] == Pocket::TYPE_AD;
                $pocket['TestMode'] = Pocket::inTestMode($pocket);

                // The frontend displays an enable/disable toggle, so we need this value to be turned around.
                $pocket['Enabled'] = $pocket['Disabled'] !== Pocket::DISABLED;
                $sender->ConditionModule->conditions(Gdn_Condition::fromString($pocket['Condition']));
                $form->setData($pocket);
            } else {
                // Default the repeat.
                $form->setFormValue('RepeatType', Pocket::REPEAT_ONCE);
            }
        }

        $sender->Form = $form;

        $sender->setData('Locations', $this->Locations);
        $sender->setData('LocationsArray', $this->getLocationsArray());
        $sender->setData('Pages', ['' => '('.t('All').')', 'activity' => 'activity', 'comments' => 'comments', 'dashboard' => 'dashboard', 'discussions' => 'discussions', 'inbox' => 'inbox', 'profile' => 'profile']);

        return $sender->render('AddEdit', '', 'plugins/Pockets');
    }

    /**
     *
     *
     * @param $sender
     * @param $pocketID
     * @return mixed
     */
    protected function _Edit($sender, $pocketID) {
        $sender->setData('Title', sprintf(t('Edit %s'), t('Pocket')));
        return $this->_AddEdit($sender, $pocketID);
    }

    /**
     *
     *
     * @param SettingsController $sender
     * @param $pocketID
     * @return bool
     * @throws Gdn_UserException
     */
    protected function _delete($sender, $pocketID) {
        $sender->setData('Title', sprintf(t('Delete %s'), t('Pocket')));

        $form = new Gdn_Form();
        if ($form->authenticatedPostBack()) {
            Gdn::sql()->delete('Pocket', ['PocketID' => $pocketID]);
            $sender->StatusMessage = sprintf(t('The %s has been deleted.'), strtolower(t('Pocket')));
            $sender->setRedirectTo('settings/pockets');
        }

        $sender->Form = $form;
        $sender->render('Delete', '', 'plugins/Pockets');
        return true;
    }

    /**
     * Add a pocket to the plugin's array of pockets to process.
     *
     * @param Pocket $pocket
     */
    public function addPocket($pocket) {
        if (!isset($this->_Pockets[$pocket->Location])) {
            $this->_Pockets[$pocket->Location] = [];
        }

        $this->_Pockets[$pocket->Location][] = $pocket;
        $this->_PocketNames[$pocket->Name][] = $pocket;
    }

    /**
     *
     *
     * @return array
     */
    public function getLocationsArray() {
        $result = [];
        foreach ($this->Locations as $key => $value) {
            $result[$key] = val('Name', $value, $key);
        }
        return $result;
    }

    /**
     *
     *
     * @param $name
     * @return mixed
     */
    public function getPockets($name) {
        $this->_loadState();
        return val($name, $this->_PocketNames, []);
    }

    /**
     *
     *
     * @param bool|false $force
     */
    protected function _loadState($force = false) {
        if (!$force && $this->StateLoaded) {
            return;
        }

        $pockets = Gdn::sql()->get('Pocket', 'Location, Sort, Name')->resultArray();
        foreach ($pockets as $row) {
            $pocket = new Pocket();
            $pocket->load($row);
            $this->addPocket($pocket);
        }

        $this->StateLoaded = true;
    }

    /**
     *
     *
     * @param $sender
     * @param $location
     * @param null $countHint
     */
    public function processPockets($sender, $location, $countHint = null) {
        if (Gdn::controller()->deliveryMethod() != DELIVERY_METHOD_XHTML) {
            return;
        }
        if (Gdn::controller()->data('_NoMessages') && $location != 'Head') {
            return;
        }

        // Since plugins can't currently maintain their state we have to stash it in the Gdn object.
        $this->_loadState();

        // Build up the data for filtering.
        $data = [];
        $data['Request'] = Gdn::request();

        // Increment the counter.
        if ($countHint != null) {
            $count = $countHint;
        } elseif (array_key_exists($location, $this->_Counters)) {
            $count = $this->_Counters[$location] + 1;
            $this->_Counters[$location] = $count;
        } else {
            $count = $this->_Counters[$location] = 1;
        }

        $data['Count'] = $count;
        $data['PageName'] = Pocket::pageName($sender);

        $locationOptions = val($location, $this->Locations, []);

        if ($this->ShowPocketLocations && array_key_exists($location, $this->Locations) && checkPermission('Plugins.Pockets.Manage') && $sender->MasterView != 'admin') {
            $locationName = val("Name", $this->Locations, $location);
            echo
                valr('Wrap.0', $locationOptions, ''),
                "<div class=\"TestPocket\"><h3>$locationName ($count)</h3></div>",
                valr('Wrap.1', $locationOptions, '');

            if ($location == 'Foot' && strcasecmp($count, 'after') == 0) {
                echo $this->testData($sender);
            }
        }

        // Process all of the pockets.
        if (array_key_exists($location, $this->_Pockets)) {
            foreach ($this->_Pockets[$location] as $pocket) {
                /** @var Pocket $Pocket */

                if ($pocket->canRender($data)) {
                    $wrap = val('Wrap', $locationOptions, []);

                    echo val(0, $wrap, '');
                    $pocket->render($data);
                    echo val(1, $wrap, '');
                }
            }
        }

        $this->_saveState();
    }

    /**
     *
     *
     * @param $name
     * @param null $data
     * @return mixed|string
     * @throws Exception
     */
    public static function pocketString($name, $data = null) {
        $inst = Gdn::pluginManager()->getPluginInstance('PocketsPlugin', Gdn_PluginManager::ACCESS_CLASSNAME);
        $pockets = $inst->getPockets($name);

        if (val('random', $data)) {
            $pockets = [array_rand($pockets)];
        }

        $result = '';
        $controllerName = Gdn::controller()->ControllerName;

        foreach ($pockets as $pocket) {
            if (val('Location', $pocket) == 'Custom' ) {
                $data['PageName'] = Pocket::pageName($controllerName);
                if ($pocket->canRender($data)) {
                    $result .= $pocket->toString();
                }
            } else {
                $result .= $pocket->toString();
            }
        }

        if (is_array($data)) {
            $data = array_change_key_case($data);

            $callback = function ($matches) use ($data) {
                $key = strtolower($matches[1]);
                if (isset($data[$key])) {
                    return $data[$key];
                } else {
                    return '';
                }
            };

            $result = preg_replace_callback('/{{(\w+)}}/', $callback, $result);
        }

        return $result;
    }

    /**
     * DEPRECATED - Callback function for preg_replace_callback
     *
     * @deprecated 2.4
     * @param array|null $match
     * @param bool|false $setArgs
     * @return string
     */
    public static function pocketStringCb($match = null, $setArgs = false) {
        deprecated(__METHOD__);

        static $data;
        if ($setArgs) {
            $data = $match;
        }

        $key = strtolower($match[1]);
        if (isset($data[$key])) {
            return $data[$key];
        } else {
            return '';
        }
    }

    /**
     * derp?
     */
    protected function _saveState() {
    }

    /**
     * Runs on enable.
     */
    public function setup() {
        $this->structure();
    }

    /**
     * Runs on utility/update.
     *
     * @throws Exception
     */
    public function structure() {
        // Pocket class isn't autoloaded on Enable.
        require_once('library/class.pocket.php');

        $St = Gdn::structure();
        $St->table('Pocket')
            ->primaryKey('PocketID')
            ->column('Name', 'varchar(255)')
            ->column('Page', 'varchar(50)', null)
            ->column('Location', 'varchar(50)')
            ->column('Sort', 'smallint')
            ->column('Repeat', 'varchar(25)')
            ->column('Body', 'text')
            ->column('Format', 'varchar(20)')
            ->column('Condition', 'varchar(500)', null)
            ->column('Disabled', 'smallint', '0') // set to a constant in class Pocket
            ->column('Attributes', 'text', null)
            ->column('MobileOnly', 'tinyint', '0')
            ->column('MobileNever', 'tinyint', '0')
            ->column('EmbeddedNever', 'tinyint', '0')
            ->column('ShowInDashboard', 'tinyint', '0')
            ->column('TestMode', 'tinyint', '0')
            ->column('Type', [Pocket::TYPE_DEFAULT, Pocket::TYPE_AD], Pocket::TYPE_DEFAULT)
            ->set();

        $PermissionModel = Gdn::permissionModel();
        $PermissionModel->define([
            'Garden.NoAds.Allow' => 0
        ]);
    }

    /**
     * derp?
     *
     * @param $sender
     */
    public function testData($sender) {
        return;
        echo "<div class=\"TestPocket\"><h3>Test Data</h3>";
        echo '<ul class="Variables">';

        echo self::_var('path', Gdn::request()->path());
        echo self::_var('page', Pocket::pageName($sender));

        echo '</ul>';
        echo "</div>";
    }

    /**
     *
     *
     * @param $name
     * @param $value
     * @return string
     */
    protected static function _var($name, $value) {
        return '<li class="Var"><b>'.htmlspecialchars($name).'</b><span>'.htmlspecialchars($value).'</span></li>';
    }
}

if (!function_exists('ValidateIntegerArray')) {
    /**
     *
     *
     * @param $value
     * @param $field
     * @return bool
     */
    function validateIntegerArray($value, $field) {
        $values = explode(',', $value);
        foreach ($values as $val) {
            if ($val && !validateInteger(trim($val)))
                return false;
        }

        return true;
    }
}


if (!function_exists('renderPocketToggle')) {

    /**
     * Renders a pocket enable/disable toggle for in a table.
     *
     * @param array $pocket Requires 'PocketID' and 'Disabled' keys.
     * @return string The enable/disable toggle for a pocket to appear in a table.
     */
    function renderPocketToggle($pocket) {
        $enabled = val('Disabled', $pocket) !== Pocket::DISABLED;
        $return = '<span id="pockets-toggle-'.val('PocketID', $pocket).'">';
        if ($enabled) {
            $return .= wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/settings/pockets/disable/'.val('PocketID', $pocket), 'Hijack'), 'span', ['class' => "toggle-wrap toggle-wrap-on"]);
        } else {
            $return .= wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/settings/pockets/enable/'.val('PocketID', $pocket), 'Hijack'), 'span', ['class' => "toggle-wrap toggle-wrap-off"]);
        }
        $return .= '</span>';
        return $return;
    }
}
