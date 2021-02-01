<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Vanilla\Addons\Pockets\PocketsModel;
use Vanilla\Widgets\WidgetFactory;
use Vanilla\Widgets\WidgetService;

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

    /** @var array */
    private $userRoleIDs = null;

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

        if (Gdn::themeFeatures()->useDataDrivenTheme()) {
            $this->Locations['AfterBanner'] = ['Name' => 'After Banner'];
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
        /* @var NestedCollectionAdapter $menu */
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
     * Hook into after banner pocket location
     *
     * @param Gdn_Controller $sender
     * @param array $args
     * @return string
     */
    public function afterBanner_handler($sender, $args = []) {
        ob_start();
        $this->processPockets($sender, "AfterBanner");
        $output = ob_get_clean();
        return $output;
    }

    /**
     * Main list for a pocket management.
     *
     * @param SettingsController $sender
     * @param array $args
     * @return mixed
     */
    public function settingsController_pockets_create($sender, $args = []) {
        $sender->permission('Plugins.Pockets.Manage');
        $sender->setHighlightRoute('settings/pockets');
        $sender->addJsFile('pockets.js', 'plugins/pockets');

        $args += [null, ''];
        $page = $args[0];
        switch (strtolower($page)) {
            case 'add':
                return $this->_add($sender);
                break;
            case 'edit':
                return $this->_edit($sender, $args[1]);
                break;
            case 'delete':
                return $this->_delete($sender, $args[1]);
                break;
            case 'enable':
                return $this->_enable($sender, $args[1]);
                break;
            case 'disable':
                return $this->_disable($sender, $args[1]);
                break;
            case 'toggle-locations':
                return $this->toggleLocations($sender);
                break;
            default:
                return $this->_index($sender);
        }
    }

    /**
     * Render the /settings/pockets page.
     *
     * @param SettingsController $sender The controller instance.
     */
    protected function _index($sender) {
        $sender->setData('Title', t('Pockets'));

        // Grab the pockets from the DB.
        $model = new PocketsModel();
        $pocketData = $model->getAll();

        /** @var WidgetService $widgetService */
        $widgetService = \Gdn::getContainer()->get(WidgetService::class);
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

            if ($pocketRow['Format'] === PocketsModel::FORMAT_WIDGET && ($pocketRow['WidgetID'] ?? false)) {
                $widgetFactory = $widgetService->getFactoryByID($pocketRow['WidgetID']);
                if ($widgetFactory !== null) {
                    $pocketRow['RenderedSummary'] = $widgetFactory->renderWidgetSummary($pocketRow['WidgetParameters'] ?? []);
                }
            }
            // If there was no widget
            if (!isset($pocketRow['RenderedSummary'])) {
                $bodyContent = nl2br(htmlspecialchars(substr($pocketRow['Body'], 0, 200)));
                $pocketRow['RenderedSummary'] = '<pre style="white-space: pre-wrap;">' . $bodyContent . '</pre>';
            }
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
     */
    private function setDisabled($sender, $pocketID, $disabledState) {
        $sender->permission('Plugins.Pockets.Manage');
        Gdn::request()->isAuthenticatedPostBack(true);

        if (empty($pocketID)) {
            $sender->errorMessage('Must specify pocket ID.');
        }

        $values = [
            'PocketID' => $pocketID,
            'Disabled' => $disabledState
        ];

        $pocketModel = \Gdn::getContainer()->get(PocketsModel::class);
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
        $pocketModel = new PocketsModel();
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
            $location = $form->getFormValue("Location");
            if ($location === "AfterBanner" || !$repeat) {
                $repeat = Pocket::REPEAT_ONCE;
            }

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
        }
        if ($pocketID !== false) {
            // Load the pocket.
            $pocket = $pocketModel->getID($pocketID);
            if (!$pocket) {
                return Gdn::dispatcher()->dispatch('Default404');
            }

            // Convert some of the pocket data into a format digestable by the form.
            [$repeatType, $repeatFrequency] = Pocket::parseRepeat($pocket['Repeat']);
            $repeatFrequency += [1, 1];

            $pocket['RepeatType'] = $repeatType;
            $pocket['EveryFrequency'] = $repeatFrequency[0];
            $pocket['EveryBegin'] = $repeatFrequency[1];
            $pocket['Indexes'] = implode(',', $repeatFrequency);
            $pocket['Ad'] = $pocket['Type'] == Pocket::TYPE_AD;
            $pocket['TestMode'] = Pocket::inTestMode($pocket);

            // The frontend displays an enable/disable toggle, so we need this value to be turned around.
            $pocket['Enabled'] = $pocket['Disabled'] !== Pocket::DISABLED;
            $sender->ConditionModule->conditions(Gdn_Condition::fromString($pocket['Condition']));
            $form->setData($pocket);

            $contentProps = [
                'widgetID' => $pocket['WidgetID'] ?? null,
                'initialWidgetParameters' => $pocket['WidgetParameters'] ?? new stdClass(),
                'initialBody' => $pocket['Body'] ?? '',
                'format' => strtolower($pocket['Format']),
            ];
            $sender->setData('contentProps', json_encode($contentProps, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
        } else {
            // Default the repeat.
            $form->setValue('RepeatType', Pocket::REPEAT_ONCE);
            $form->setValue('Location', 'Panel');
        }

        $sender->Form = $form;

        $sender->setData('Locations', $this->Locations);
        $sender->setData('LocationsArray', $this->getLocationsArray());
        $sender->setData('Attributes', json_decode($form->getFormValue("Attributes", '[]')));
        $sender->setData(
            'Pages',
            [
                '' => '('.t('All').')',
                'home' => 'Home',
                'activity' => 'Activity',
                'comments' => 'Comments',
                'dashboard' => 'Dashboard',
                'discussions' => 'Discussions',
                'categories' => 'Categories',
                'inbox' => 'Inbox',
                'profile' => 'Profile',
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
            $model = new PocketsModel();
            $model->delete(['PocketID' => $pocketID]);
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
        $result = [
            '' => sprintf(t('Select a %s'), t('Location')),
        ];
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
     * @return array
     */
    private function getUserRoleIDs(): array {
        if ($this->userRoleIDs) {
            return $this->userRoleIDs;
        }
        $roleModel = Gdn::getContainer()->get(RoleModel::class);
        if (Gdn::session()->isValid()) {
            $userID = Gdn::session()->UserID;
            $roles = $roleModel->getByUserID($userID)->resultArray();
            $this->userRoleIDs = array_column($roles, 'RoleID');
        } else {
            $roles = $roleModel->getByType('guest')->resultArray();
            $this->userRoleIDs = array_column($roles, 'RoleID');
        }

        return $this->userRoleIDs;
    }

    /**
     * @param array|null $roleIDs
     */
    public function setUserRoleIDs(?array $roleIDs): void {
        $this->userRoleIDs = $roleIDs;
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

        $widgetService = \Gdn::getContainer()->get(WidgetService::class);
        $model = \Gdn::getContainer()->get(PocketsModel::class);
        $pockets = $model->getEnabled();
        foreach ($pockets as $row) {
            $pocket = new Pocket($widgetService);
            $pocket->load($row);
            $this->addPocket($pocket);
        }

        $this->StateLoaded = true;
    }

    /**
     * Clear the internal pockets state.
     */
    public function resetState() {
        $this->StateLoaded = false;
        $this->_PocketNames = [];
        $this->_Pockets = [];
    }

    /**
     * Gather all of the pockets for a particular page.
     *
     * @param Gdn_Controller $sender The controller instance.
     * @param string $location The pocket location.
     * @param number|null $countHint
     */
    public function processPockets($sender, $location, $countHint = null) {
        $controller = $sender;
        if ((!$controller instanceof Gdn_Controller)) {
            $controller = Gdn::controller();
        }

        if ($controller->deliveryMethod() != DELIVERY_METHOD_XHTML) {
            return;
        }
        if ($controller->data('_NoMessages') && $location != 'Head' && $location !== 'AfterBanner') {
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
        $data['PageName'] = Pocket::pageName($controller);
        $data['isHomepage'] = $controller->data('isHomepage');

        $locationOptions = val($location, $this->Locations, []);

        if ($this->ShowPocketLocations &&
            array_key_exists($location, $this->Locations) &&
            checkPermission('Plugins.Pockets.Manage') && $controller->MasterView != 'admin') {
            $locationName = val("Name", $this->Locations, $location);
            echo
                valr('Wrap.0', $locationOptions, ''),
                "<div class=\"TestPocket\"><h3>$locationName ($count)</h3></div>",
                valr('Wrap.1', $locationOptions, '');

            if ($location == 'Foot' && strcasecmp($count, 'after') == 0) {
                echo $this->testData($controller);
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
            ->column('WidgetID', 'varchar(300)', null, 'index.WidgetID')
            ->column('Name', 'varchar(255)')
            ->column('Page', 'varchar(50)', null)
            ->column('Location', 'varchar(50)')
            ->column('Sort', 'smallint')
            ->column('Repeat', 'varchar(25)')
            ->column('Body', 'text')
            ->column('Format', 'varchar(20)', 'index.Format')
            ->column('Condition', 'varchar(500)', null)
            ->column('Disabled', 'smallint', '0', 'index') // set to a constant in class Pocket
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
     * Return the toggle UI for toggling pocket locations.
     *
     * @param bool $on Whether or not the toggle is currently on.
     * @return string Returns an HTML string.
     */
    public static function locationsToggle(bool $on): string {
        $r = wrap(
            anchor(
                '<div class="toggle-well"></div><div class="toggle-slider"></div>',
                '/settings/pockets/toggle-locations'.($on ? '?hide=1' : ''),
                'js-hijack'
            ),
            'span',
            ['class' => "toggle-wrap toggle-wrap-".($on ? 'on' : 'off')]
        );

        return $r;
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
        $on = !$sender->Request->get('hide');
        saveToConfig('Plugins.Pockets.ShowLocations', $on, ['RemoveEmpty' => true]);

        $sender->jsonTarget('#pocket-locations-toggle', static::locationsToggle($on), 'Html');
        $sender->render('blank', 'utility', 'dashboard');
    }

    /**
     * Add multi role input to pocket filters
     *
     * @param array $args
     */
    public function settingsController_additionalPocketFilterInputs_handler($args) {
        $Form = $args['form'];

        echo $Form->react(
            "RoleIDs",
            "pocket-multi-role-input",
            [
                "tag" => "li",
            ]
        );

        $data = $Form->formData();
        $categoryID = $data["CategoryID"];
        $categoryLabel = null;
        if (!empty($categoryID)) {
            $currentCategory = CategoryModel::categories($categoryID);
            $categoryLabel = $currentCategory['Name'];
        }

        echo $Form->react(
            "CategoryID",
            "pocket-category-input",
            [
                "tag" => "li",
                "label" => $categoryLabel,
                "inheritCategory" => boolval($data["InheritCategory"] ?? 0)
            ]
        );
    }


    /**
     * Add some event handling for pocket rendering. - Roles
     *
     * @param bool $existingCanRender
     * @param Pocket $pocket
     * @param array $requestData
     *
     *
     * @return bool
     */
    private function canRenderRoles(bool $existingCanRender, Pocket $pocket, array $requestData) {
        if (!$existingCanRender) {
            return $existingCanRender;
        }

        $testMode = Pocket::inTestMode($pocket);
        $pocketAdmin = checkPermission('Plugins.Pockets.Manage');
        $pocketData = $pocket->Data;
        $roleIDs = $pocketData['RoleIDs'] ?? [];

        if (empty($roleIDs)) {
            return $existingCanRender;
        }

        if ($testMode && $pocketAdmin) {
            return $existingCanRender;
        }

        $intersections = array_intersect($this->getUserRoleIDs(), $roleIDs);
        if (count($intersections) === 0) {
            return false;
        }
        return true;
    }

    /**
     * Add some event handling for pocket rendering. - Categories
     *
     * @param bool $existingCanRender
     * @param Pocket $pocket
     * @param array $requestData
     *
     *
     * @return bool
     */
    private function canRenderCategories(bool $existingCanRender, Pocket $pocket, array $requestData) {
        if (!$existingCanRender) {
            return $existingCanRender;
        }

        $pocketData = $pocket->Data;
        $categoryID = $pocketData['CategoryID'] ?? null;

        if (empty($categoryID)) {
            return $existingCanRender;
        }

        if (!is_numeric($categoryID)) {
            return false;
        } else {
            $categoryID = (int) $categoryID;
        }

        $controller = \Gdn::controller();
        if (!$controller) {
            return false;
        }

        $currentCategoryID = $controller->data('Category.CategoryID', $controller->data('ContextualCategoryID'));

        if (!$currentCategoryID) {
            return false;
        }

        if (!($pocketData["InheritCategory"] ?? false)) {
            if ($currentCategoryID !== $categoryID) {
                return false;
            }
        } else {
            $ancestors = CategoryModel::getAncestors($currentCategoryID, true);
            $ancestorIDs = array_column($ancestors, 'CategoryID');
            $ancestorIDs[] = $currentCategoryID;
            $ancestorIDs[] = -1;
            $result = array_search($categoryID, $ancestorIDs, true) !== false;
            return (bool) $result;
        }

        return $existingCanRender;
    }

    /**
     * Add some event handling for pocket rendering. - Roles
     *
     * @param bool $existingCanRender
     * @param Pocket $pocket
     * @param array $requestData
     *
     *
     * @return bool
     */
    public function pocket_canRender_handler(bool $existingCanRender, Pocket $pocket, array $requestData): bool {
        $existingCanRender = $this->canRenderRoles($existingCanRender, $pocket, $requestData);
        $existingCanRender = $this->canRenderCategories($existingCanRender, $pocket, $requestData);
        return $existingCanRender;
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
