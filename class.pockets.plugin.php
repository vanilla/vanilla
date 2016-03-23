<?php
/**
 * @copyright 2008-2016 Vanilla Forums, Inc.
 * @license GNU GPLv2
 */

$PluginInfo['Pockets'] = array(
    'Name' => 'Pockets',
    'Description' => 'Administrators may add raw HTML to various places on the site. This plugin is very powerful, but can easily break your site if you make a mistake.',
    'Version' => '1.2',
    'Author' => "Todd Burry",
    'AuthorEmail' => 'todd@vanillaforums.com',
    'AuthorUrl' => 'http://vanillaforums.org/profile/todd',
    'RequiredApplications' => array('Vanilla' => '2.1'),
    'RegisterPermissions' => array('Plugins.Pockets.Manage' => 'Garden.Settings.Manage', 'Garden.NoAds.Allow'),
    'SettingsUrl' => '/settings/pockets',
    'SettingsPermission' => 'Plugins.Pockets.Manage',
    'MobileFriendly' => true,
    'HasLocale' => true,
    'License' => 'GNU GPLv2'
);

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
    public $TestMode = null;

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
        if ($this->TestMode === null) {
            $this->TestMode = c('Plugins.Pockets.ShowLocations');
        }
        if ($this->TestMode && checkPermission('Plugins.Pockets.Manage')) {
            // Add the css for the test pockets to the page.
            $Sender->addCSSFile('pockets.css', 'plugins/Pockets');
        }
    }

    /**
     * Adds "Media" menu option to the Forum menu on the dashboard.
     *
     * @param $Sender
     */
    public function base_GetAppSettingsMenuItems_Handler(&$Sender) {
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
        $Sender->addSideMenu('settings/pockets');
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
            // Add notes for the display.
            $Notes = array();

            if ($PocketRow['Repeat'] && $PocketRow['Repeat'] != Pocket::REPEAT_ONCE)
                $PocketRow['Location'] .= " ({$PocketRow['Repeat']})";

            if ($PocketRow['Disabled'] == Pocket::DISABLED)
                $Notes[] = T('Disabled');
            elseif ($PocketRow['Disabled'] == Pocket::TESTING)
                $Notes[] = T('Testing');

            $PocketRow['Notes'] = implode(', ', $Notes);
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
     *
     *
     * @param $Sender
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

            $Saved = $Form->save();
            if ($Saved) {
                $Sender->StatusMessage = t('Your changes have been saved.');
                $Sender->RedirectUrl = url('settings/pockets');
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
     * @param $Sender
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
            $Sender->RedirectUrl = Url('settings/pockets');
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

        if ($this->TestMode && array_key_exists($Location, $this->Locations) && checkPermission('Plugins.Pockets.Manage')) {
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
