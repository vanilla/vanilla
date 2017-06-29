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
    protected $_Counters = array();

    /** @var array  */
    public $Locations = array(
        'Content' => array('Name' => 'Content'),
        'Panel' => array('Name' => 'Panel'),
        'BetweenDiscussions' => array('Name' => 'Between Discussions', 'Wrap' => array('<li>', '</li>')),
        'BetweenComments' => array('Name' => 'Between Comments', 'Wrap' => array('<li>', '</li>')),
        'Head' => array('Name' => 'Head'),
        'Foot' => array('Name' => 'Foot'),
        'Custom' => array('Name' => 'Custom')
    );

    /** @var array All of the pockets indexed by location. */
    protected $_Pockets = array();

    /** @var array  */
    protected $_PocketNames = array();

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
     * @param $Sender
     */
    public function base_render_before($Sender) {
        if ($this->ShowPocketLocations === null) {
            $this->ShowPocketLocations = c('Plugins.Pockets.ShowLocations');
        }
        if ($this->ShowPocketLocations && checkPermission('Plugins.Pockets.Manage') && $Sender->MasterView != 'admin') {
            // Add the css for the test pockets to the page.
            $Sender->addCSSFile('pockets.css', 'plugins/Pockets');
        }
    }

    /**
     * Adds "Media" menu option to the Forum menu on the dashboard.
     *
     * @param $Sender
     */
    public function base_GetAppSettingsMenuItems_Handler($Sender) {
        $Menu = $Sender->EventArguments['SideMenu'];
        $Menu->addItem('Appearance', t('Appearance'));
        $Menu->addLink('Appearance', t('Pockets'), 'settings/pockets', 'Plugins.Pockets.Manage');
    }

    /**
     *
     *
     * @param $Sender
     */
    public function base_BeforeRenderAsset_Handler($Sender) {
        $AssetName = valr('EventArguments.AssetName', $Sender);
        $this->processPockets($Sender, $AssetName, Pocket::REPEAT_BEFORE);
    }

    /**
     *
     *
     * @param $Sender
     */
    public function base_afterRenderAsset_handler($Sender) {
        $AssetName = valr('EventArguments.AssetName', $Sender);
        $this->processPockets($Sender, $AssetName, Pocket::REPEAT_AFTER);
    }

    /**
     *
     *
     * @param $Sender
     */
    public function base_betweenRenderAsset_handler($Sender) {
        $AssetName = valr('EventArguments.AssetName', $Sender);
        $this->processPockets($Sender, $AssetName);
    }

    /**
     *
     *
     * @param $Sender
     */
    public function base_betweenDiscussion_handler($Sender) {
        $this->processPockets($Sender, 'BetweenDiscussions');
    }

    /**
     *
     *
     * @param $Sender
     */
    public function base_beforeCommentDisplay_handler($Sender) {
        // We don't want pockets to display before the first comment because they are only between comments.
        $Processed = isset($this->_Counters['BeforeCommentDisplay']);
        if (!$Processed) {
            $this->_Counters['BeforeCommentDisplay'] = true;
            return;
        }
        $this->processPockets($Sender, 'BetweenComments');
    }

    /**
     * Main list for a pocket management.
     *
     * @param Gdn_Controller $Sender.
     * @param array $Args
     * @return mixed
     */
    public function settingsController_pockets_create($Sender, $Args = array()) {
        $Sender->permission('Plugins.Pockets.Manage');
        $Sender->setHighlightRoute('settings/pockets');
        $Sender->addJsFile('pockets.js', 'plugins/Pockets');

        $Page = val(0, $Args);
        switch(strtolower($Page)) {
            case 'add':
                return $this->_add($Sender);
                break;
            case 'edit':
                return $this->_edit($Sender, val(1, $Args));
                break;
            case 'delete':
                return $this->_delete($Sender, val(1, $Args));
                break;
            case 'enable':
                return $this->_enable($Sender, val(1, $Args));
                break;
            case 'disable':
                return $this->_disable($Sender, val(1, $Args));
                break;
            default:
                return $this->_index($Sender, $Args);
        }
    }

    /**
     *
     *
     * @param $Sender
     * @param $Args
     */
    protected function _index($Sender, $Args) {
        $Sender->setData('Title', t('Pockets'));

        // Grab the pockets from the DB.
        $PocketData = Gdn::sql()
            ->get('Pocket', 'Location, `Sort`')
            ->resultArray();

        // Add notes to the pockets data.
        foreach ($PocketData as $Index => &$PocketRow) {

            $mobileOnly = $PocketRow['MobileOnly'];
            $mobileNever = $PocketRow['MobileNever'];
            $noAds = $PocketRow['Type'] == Pocket::TYPE_AD;
            $testing = Pocket::inTestMode($PocketRow);
            $meta = [];

            if ($PocketRow['Repeat'] && $PocketRow['Repeat'] != Pocket::REPEAT_ONCE) {
                $PocketRow['Location'] .= " ({$PocketRow['Repeat']})";
            }

            if ($location = htmlspecialchars($PocketRow['Location'])) {
                $meta['location'] = [
                    'label' => t('Location'),
                    'value' => $location
                ];
            }
            if ($page = htmlspecialchars($PocketRow['Page'])) {
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

            $PocketRow['Meta'] = $meta;
        }

        $Sender->setData('PocketData', $PocketData);

        $Form = new Gdn_Form();

        // Save global options.
        switch (val(0, $Args)) {
            case 'showlocations':
                saveToConfig('Plugins.Pockets.ShowLocations', true);
                break;
            case 'hidelocations':
                saveToConfig('Plugins.Pockets.ShowLocations', false, array('RemoveEmpty' => true));
                break;
        }

        $Sender->Form = $Form;
        $Sender->render('Index', '', 'plugins/Pockets');
    }

    /**
     *
     *
     * @param $Sender
     * @return mixed
     */
    protected function _add($Sender) {
        $Sender->setData('Title', sprintf(t('Add %s'), t('Pocket')));
        return $this->_addEdit($Sender);
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
     * @param SettingsController $Sender
     * @param bool|false $PocketID
     * @return mixed
     * @throws Gdn_UserException
     */
    protected function _addEdit($Sender, $PocketID = false) {
        $Form = new Gdn_Form();
        $PocketModel = new Gdn_Model('Pocket');
        $Form->setModel($PocketModel);
        $Sender->ConditionModule = new ConditionModule($Sender);
        $Sender->Form = $Form;

        if ($Form->authenticatedPostBack()) {
            // Save the pocket.
            if ($PocketID !== false) {
                $Form->setFormValue('PocketID', $PocketID);
            }

            // Convert the form data into a format digestable by the database.
            $Repeat = $Form->getFormValue('RepeatType');
            switch ($Repeat) {
                case Pocket::REPEAT_EVERY:
                    $PocketModel->Validation->applyRule('EveryFrequency', 'Integer');
                    $PocketModel->Validation->applyRule('EveryBegin', 'Integer');
                    $Frequency = $Form->getFormValue('EveryFrequency', 1);
                    if (!$Frequency || !validateInteger($Frequency) || $Frequency < 1)
                        $Frequency = 1;
                    $Repeat .= ' '.$Frequency;
                    if ($Form->getFormValue('EveryBegin', 1) > 1)
                        $Repeat .= ','.$Form->getFormValue('EveryBegin');
                    break;
                case Pocket::REPEAT_INDEX:
                    $PocketModel->Validation->addRule('IntegerArray', 'function:ValidateIntegerArray');
                    $PocketModel->Validation->applyRule('Indexes', 'IntegerArray');
                    $Indexes = explode(',', $Form->getFormValue('Indexes', ''));
                    $Indexes = array_map('trim', $Indexes);
                    $Repeat .= ' '.implode(',', $Indexes);
                    break;
                default:
                    break;
            }
            $Form->setFormValue('Repeat', $Repeat);
            $Form->setFormValue('Sort', 0);
            $Form->setFormValue('Format', 'Raw');
            $Condition = Gdn_Condition::toString($Sender->ConditionModule->conditions(true));
            $Form->setFormValue('Condition', $Condition);
            if ($Form->getFormValue('Ad', 0)) {
                $Form->setFormValue('Type', Pocket::TYPE_AD);
            } else {
                $Form->setFormValue('Type', Pocket::TYPE_DEFAULT);
            }

            // Deprecating the 3-state Disabled field (enabled, disabled, testing) in favour of a separate 'TestMode'
            // field. All testing pockets should be enabled with the testing flag set.
            if ($Form->getFormValue('Disabled') === Pocket::TESTING) {
                $Form->setFormValue('Disabled', Pocket::ENABLED);
                // The 'TestMode' property will already be set to true in the UI, we'll let save() set it.
            }

            $enabled = $Form->getFormValue('Enabled');
            $Form->setFormValue('Disabled', $enabled === "1" ? Pocket::ENABLED : Pocket::DISABLED);

            $Saved = $Form->save();
            if ($Saved) {
                $Sender->StatusMessage = t('Your changes have been saved.');
                $Sender->setRedirectTo('settings/pockets', false);
            }
        } else {
            if ($PocketID !== false) {
                // Load the pocket.
                $Pocket = $PocketModel->getWhere(array('PocketID' => $PocketID))->firstRow(DATASET_TYPE_ARRAY);
                if (!$Pocket) {
                    return Gdn::dispatcher()->dispatch('Default404');
                }

                // Convert some of the pocket data into a format digestable by the form.
                list($RepeatType, $RepeatFrequency) = Pocket::parseRepeat($Pocket['Repeat']);
                $Pocket['RepeatType'] = $RepeatType;
                $Pocket['EveryFrequency'] = GetValue(0, $RepeatFrequency, 1);
                $Pocket['EveryBegin'] = GetValue(1, $RepeatFrequency, 1);
                $Pocket['Indexes'] = implode(',', $RepeatFrequency);
                $Pocket['Ad'] = $Pocket['Type'] == Pocket::TYPE_AD;
                $Pocket['TestMode'] = Pocket::inTestMode($Pocket);

                // The frontend displays an enable/disable toggle, so we need this value to be turned around.
                $Pocket['Enabled'] = $Pocket['Disabled'] !== Pocket::DISABLED;
                $Sender->ConditionModule->conditions(Gdn_Condition::fromString($Pocket['Condition']));
                $Form->setData($Pocket);
            } else {
                // Default the repeat.
                $Form->setFormValue('RepeatType', Pocket::REPEAT_ONCE);
            }
        }

        $Sender->Form = $Form;

        $Sender->setData('Locations', $this->Locations);
        $Sender->setData('LocationsArray', $this->getLocationsArray());
        $Sender->setData('Pages', array('' => '('.T('All').')', 'activity' => 'activity', 'comments' => 'comments', 'dashboard' => 'dashboard', 'discussions' => 'discussions', 'inbox' => 'inbox', 'profile' => 'profile'));

        return $Sender->render('AddEdit', '', 'plugins/Pockets');
    }

    /**
     *
     *
     * @param $Sender
     * @param $PocketID
     * @return mixed
     */
    protected function _Edit($Sender, $PocketID) {
        $Sender->setData('Title', sprintf(T('Edit %s'), T('Pocket')));
        return $this->_AddEdit($Sender, $PocketID);
    }

    /**
     *
     *
     * @param SettingsController $Sender
     * @param $PocketID
     * @return bool
     * @throws Gdn_UserException
     */
    protected function _delete($Sender, $PocketID) {
        $Sender->setData('Title', sprintf(t('Delete %s'), t('Pocket')));

        $Form = new Gdn_Form();
        if ($Form->authenticatedPostBack()) {
            Gdn::sql()->delete('Pocket', array('PocketID' => $PocketID));
            $Sender->StatusMessage = sprintf(T('The %s has been deleted.'), strtolower(t('Pocket')));
            $Sender->setRedirectTo('settings/pockets', false);
        }

        $Sender->Form = $Form;
        $Sender->render('Delete', '', 'plugins/Pockets');
        return true;
    }

    /**
     * Add a pocket to the plugin's array of pockets to process.
     *
     * @param Pocket $Pocket
     */
    public function addPocket($Pocket) {
        if (!isset($this->_Pockets[$Pocket->Location])) {
            $this->_Pockets[$Pocket->Location] = array();
        }

        $this->_Pockets[$Pocket->Location][] = $Pocket;
        $this->_PocketNames[$Pocket->Name][] = $Pocket;
    }

    /**
     *
     *
     * @return array
     */
    public function getLocationsArray() {
        $Result = array();
        foreach ($this->Locations as $Key => $Value) {
            $Result[$Key] = val('Name', $Value, $Key);
        }
        return $Result;
    }

    /**
     *
     *
     * @param $Name
     * @return mixed
     */
    public function getPockets($Name) {
        $this->_loadState();
        return val($Name, $this->_PocketNames, array());
    }

    /**
     *
     *
     * @param bool|false $Force
     */
    protected function _loadState($Force = false) {
        if (!$Force && $this->StateLoaded) {
            return;
        }

        $Pockets = Gdn::sql()->get('Pocket', 'Location, Sort, Name')->resultArray();
        foreach ($Pockets as $Row) {
            $Pocket = new Pocket();
            $Pocket->load($Row);
            $this->addPocket($Pocket);
        }

        $this->StateLoaded = true;
    }

    /**
     *
     *
     * @param $Sender
     * @param $Location
     * @param null $CountHint
     */
    public function processPockets($Sender, $Location, $CountHint = null) {
        if (Gdn::controller()->deliveryMethod() != DELIVERY_METHOD_XHTML) {
            return;
        }
        if (Gdn::controller()->data('_NoMessages') && $Location != 'Head') {
            return;
        }

        // Since plugins can't currently maintain their state we have to stash it in the Gdn object.
        $this->_loadState();

        // Build up the data for filtering.
        $Data = array();
        $Data['Request'] = Gdn::request();

        // Increment the counter.
        if ($CountHint != null) {
            $Count = $CountHint;
        } elseif (array_key_exists($Location, $this->_Counters)) {
            $Count = $this->_Counters[$Location] + 1;
            $this->_Counters[$Location] = $Count;
        } else {
            $Count = $this->_Counters[$Location] = 1;
        }

        $Data['Count'] = $Count;
        $Data['PageName'] = Pocket::pageName($Sender);

        $LocationOptions = val($Location, $this->Locations, array());

        if ($this->ShowPocketLocations && array_key_exists($Location, $this->Locations) && checkPermission('Plugins.Pockets.Manage') && $Sender->MasterView != 'admin') {
            $LocationName = val("Name", $this->Locations, $Location);
            echo
                valr('Wrap.0', $LocationOptions, ''),
                "<div class=\"TestPocket\"><h3>$LocationName ($Count)</h3></div>",
                valr('Wrap.1', $LocationOptions, '');

            if ($Location == 'Foot' && strcasecmp($Count, 'after') == 0) {
                echo $this->testData($Sender);
            }
        }

        // Process all of the pockets.
        if (array_key_exists($Location, $this->_Pockets)) {
            foreach ($this->_Pockets[$Location] as $Pocket) {
                /** @var Pocket $Pocket */

                if ($Pocket->canRender($Data)) {
                    $Wrap = val('Wrap', $LocationOptions, array());

                    echo val(0, $Wrap, '');
                    $Pocket->render($Data);
                    echo val(1, $Wrap, '');
                }
            }
        }

        $this->_saveState();
    }

    /**
     *
     *
     * @param $Name
     * @param null $Data
     * @return mixed|string
     * @throws Exception
     */
    public static function pocketString($Name, $Data = null) {
        $Inst = Gdn::pluginManager()->getPluginInstance('PocketsPlugin', Gdn_PluginManager::ACCESS_CLASSNAME);
        $Pockets = $Inst->getPockets($Name);

        if (val('random', $Data)) {
            $Pockets = array(array_rand($Pockets));
        }

        $Result = '';
        $ControllerName = Gdn::controller()->ControllerName;

        foreach ($Pockets as $Pocket) {
            if (val('Location', $Pocket) == 'Custom' ) {
                $Data['PageName'] = Pocket::pageName($ControllerName);
                if ($Pocket->canRender($Data)) {
                    $Result .= $Pocket->toString();
                }
            } else {
                $Result .= $Pocket->toString();
            }
        }

        if (is_array($Data)) {
            $Data = array_change_key_case($Data);

            self::pocketStringCb($Data, true);
            $Result = preg_replace_callback('`{{(\w+)}}`', array('PocketsPlugin', 'PocketStringCb'), $Result);
        }

        return $Result;
    }

    /**
     *
     *
     * @param null $Match
     * @param bool|false $SetArgs
     * @return string
     */
    public static function pocketStringCb($Match = null, $SetArgs = false) {
        static $Data;
        if ($SetArgs) {
            $Data = $Match;
        }

        $Key = strtolower($Match[1]);
        if (isset($Data[$Key])) {
            return $Data[$Key];
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
            ->column('Type', array(Pocket::TYPE_DEFAULT, Pocket::TYPE_AD), Pocket::TYPE_DEFAULT)
            ->set();

        $PermissionModel = Gdn::permissionModel();
        $PermissionModel->define(array(
            'Garden.NoAds.Allow' => 0
        ));
    }

    /**
     * derp?
     *
     * @param $Sender
     */
    public function testData($Sender) {
        return;
        echo "<div class=\"TestPocket\"><h3>Test Data</h3>";
        echo '<ul class="Variables">';

        echo self::_var('path', Gdn::request()->path());
        echo self::_var('page', Pocket::pageName($Sender));

        echo '</ul>';
        echo "</div>";
    }

    /**
     *
     *
     * @param $Name
     * @param $Value
     * @return string
     */
    protected static function _var($Name, $Value) {
        return '<li class="Var"><b>'.htmlspecialchars($Name).'</b><span>'.htmlspecialchars($Value).'</span></li>';
    }
}

if (!function_exists('ValidateIntegerArray')) {
    /**
     *
     *
     * @param $Value
     * @param $Field
     * @return bool
     */
    function validateIntegerArray($Value, $Field) {
        $Values = explode(',', $Value);
        foreach ($Values as $Val) {
            if ($Val && !validateInteger(trim($Val)))
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
            $return .= wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/settings/pockets/disable/'.val('PocketID', $pocket), 'Hijack'), 'span', array('class' => "toggle-wrap toggle-wrap-on"));
        } else {
            $return .= wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/settings/pockets/enable/'.val('PocketID', $pocket), 'Hijack'), 'span', array('class' => "toggle-wrap toggle-wrap-off"));
        }
        $return .= '</span>';
        return $return;
    }
}
