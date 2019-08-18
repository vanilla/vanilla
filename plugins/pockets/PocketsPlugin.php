<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
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
     * @param Gdn_Controller $sender The controller firing the event.
     */
    public function base_render_before($sender) {
        if ($this->ShowPocketLocations === null) {
            $this->ShowPocketLocations = c('Plugins.Pockets.ShowLocations');
        }
        if ($this->ShowPocketLocations && checkPermission('Plugins.Pockets.Manage') && $sender->MasterView != 'admin') {
            // Add the css for the test pockets to the page.
            $sender->addCSSFile('pockets.css', 'plugins/pockets');
        }
    }

    /**
     * Adds "Media" menu option to the Forum menu on the dashboard.
     *
     * @param Gdn_Pluggable $sender The firing pluggable instance.
     */
    public function base_getAppSettingsMenuItems_handler($sender) {
        $menu = $sender->EventArguments['SideMenu'];
        $menu->addItem('Appearance', t('Appearance'));
        $menu->addLink('Appearance', t('Pockets'), 'settings/pockets', 'Plugins.Pockets.Manage');
    }

    /**
     * Render pockets for that are set before a particular asset.
     *
     * @param Gdn_Controller $sender The controller rendering the asset.
     */
    public function base_beforeRenderAsset_handler($sender) {
        $assetName = valr('EventArguments.AssetName', $sender);
        $this->processPockets($sender, $assetName, Pocket::REPEAT_BEFORE);
    }

    /**
     * Render pockets for that are set after a particular asset.
     *
     * @param Gdn_Controller $sender The controller rendering the asset.
     */
    public function base_afterRenderAsset_handler($sender) {
        $assetName = valr('EventArguments.AssetName', $sender);
        $this->processPockets($sender, $assetName, Pocket::REPEAT_AFTER);
    }

    /**
     * Render pockets for that are between particular assets.
     *
     * @param Gdn_Controller $sender The controller rendering the asset.
     */
    public function base_betweenRenderAsset_handler($sender) {
        $assetName = valr('EventArguments.AssetName', $sender);
        $this->processPockets($sender, $assetName);
    }

    /**
     * Render pockets for that are set between discussions.
     *
     * @param Gdn_Controller $sender The controller rendering the asset.
     */
    public function base_betweenDiscussion_handler($sender) {
        $this->processPockets($sender, 'BetweenDiscussions');
    }

    /**
     * Render pockets for that are set after comments.
     *
     * @param Gdn_Controller $sender The controller rendering the asset.
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
     * @param Gdn_Controller $sender
     * @param array $args
     * @return mixed
     */
    public function settingsController_pockets_create($sender, $args = []) {
        $sender->permission('Plugins.Pockets.Manage');
        $sender->setHighlightRoute('settings/pockets');
        $sender->addJsFile('pockets.js', 'plugins/pockets');

        $page = $args[0] ?? null;
        switch (strtolower($page)) {
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
            case 'toggle-locations':
                return $this->toggleLocations($sender, $args);
                break;
            default:
                return $this->_index($sender, $args);
        }
    }

    /**
     * Render the /settings/pockets page.
     *
     * @param SettingsController $sender The controller instance.
     * @param array $args Routing arguments.
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
            } elseif ($mobileOnly) {
                $meta['visibility'] = [
                    'label' => t('Visibility'),
                    'value' => t('Shown only on mobile')
                ];
            } elseif ($mobileNever) {
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
        $sender->Form = $form;
        $sender->render('Index', '', 'plugins/pockets');
    }

    /**
     * Render the /settings/pockets/add page.
     *
     * @param SettingsController $sender The controller instance.
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
     * @param string $disabledState Either Pocket::ENABLED or Pocket::DISABLED
     *
     * @throws Exception If the method is called without proper authentication.
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
     * Render the /settings/pockets/add or /settings/pockets/edit page.
     *
     * @param SettingsController $sender The controller instance.
     * @param number|bool $pocketID
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
                    if (!$frequency || !validateInteger($frequency) || $frequency < 1) {
                        $frequency = 1;
                    }
                    $repeat .= ' '.$frequency;
                    if ($form->getFormValue('EveryBegin', 1) > 1) {
                        $repeat .= ','.$form->getFormValue('EveryBegin');
                    }
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
        $sender->setData(
            'Pages',
            [
                '' => '('.t('All').')',
                'activity' => 'activity',
                'comments' => 'comments',
                'dashboard' => 'dashboard',
                'discussions' => 'discussions',
                'inbox' => 'inbox',
                'profile' => 'profile',
            ]
        );

        return $sender->render('AddEdit', '', 'plugins/pockets');
    }

    /**
     * Render the /settings/pockets/edit page.
     *
     * @param SettingsController $sender The controller instance.
     * @param number $pocketID
     */
    protected function _edit($sender, $pocketID) {
        $sender->setData('Title', sprintf(t('Edit %s'), t('Pocket')));
        return $this->_AddEdit($sender, $pocketID);
    }

    /**
     * Render the /settings/pockets/delete page.
     *
     * @param SettingsController $sender The controller instance.
     * @param number $pocketID
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
        $sender->render('Delete', '', 'plugins/pockets');
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
     * Get an array mapping location keys to visual display names.
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
     * Get all with a particular name.
     *
     * @param string $name The name of the pocket.
     *
     * @return array
     */
    public function getPockets($name) {
        $this->_loadState();
        return val($name, $this->_PocketNames, []);
    }

    /**
     * Load all pockets from the database.
     *
     * @param bool $force If true, re-load data from DB even if it's loaded.
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
     * Gather all of the pockets for a particular page.
     *
     * @param Gdn_Controller $sender The controller instance.
     * @param string $location The pocket location.
     * @param number|null $countHint
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
    }

    /**
     * Get the contents of the pocket as a string.
     *
     * @param string $name The name of the pocket.
     * @param array|null $data
     *
     * @return string
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
            if (val('Location', $pocket) == 'Custom') {
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
     *
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
     * Runs on enable.
     */
    public function setup() {
        $this->structure();
    }

    /**
     * Runs on utility/update.
     */
    public function structure() {
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
     * Render debugging information for pockets.
     *
     * @param Gdn_Controller $sender
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
     * Render a list element of an item and its value.
     *
     * @param string $name The name of the item.
     * @param string $value The value to display.
     *
     * @return string
     */
    protected static function _var($name, $value) {
        return '<li class="Var"><b>'.htmlspecialchars($name).'</b><span>'.htmlspecialchars($value).'</span></li>';
    }

    /**
     * Toggle pocket locations.
     *
     * @param SettingsController $sender
     */
    private function toggleLocations(SettingsController $sender) {
        $sender->Request->isAuthenticatedPostBack(true);

        // Save global options.
        if ($sender->Request->get('hide')) {
            saveToConfig('Plugins.Pockets.ShowLocations', false, ['RemoveEmpty' => true]);
            $t = ['off', 'on'];
        } else {
            saveToConfig('Plugins.Pockets.ShowLocations', true);
            $t = ['on', 'off'];
        }

        $sender->jsonTarget('#pocket-locations-toggle .toggle-wrap', 'toggle-wrap-'.$t[0], 'AddClass');
        $sender->jsonTarget('#pocket-locations-toggle .toggle-wrap', 'toggle-wrap-'.$t[1], 'RemoveClass');

        $sender->setRedirectTo('/settings/pockets');
        $sender->render('blank', 'utility', 'dashboard');
    }
}

if (!function_exists('ValidateIntegerArray')) {
    /**
     * Validate that all values in an array are integers.
     *
     * @param mixed[] $value An array of values to validate.
     *
     * @return bool
     */
    function validateIntegerArray($value) {
        $values = explode(',', $value);
        foreach ($values as $val) {
            if ($val && !validateInteger(trim($val))) {
                return false;
            }
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
        $pocketID = val('PocketID', $pocket);
        $return = "<span id='pockets-toggle-$pocketID'>";
        $content = '<div class="toggle-well"></div><div class="toggle-slider"></div>';
        if ($enabled) {
            $return .= wrap(
                anchor(
                    $content,
                    '/settings/pockets/disable/' . $pocketID,
                    'Hijack'
                ),
                'span',
                ['class' => "toggle-wrap toggle-wrap-on"]
            );
        } else {
            $return .= wrap(
                anchor(
                    $content,
                    '/settings/pockets/enable/' . $pocketID,
                    'Hijack'
                ),
                'span',
                ['class' => "toggle-wrap toggle-wrap-off"]
            );
        }
        $return .= '</span>';
        return $return;
    }
}
