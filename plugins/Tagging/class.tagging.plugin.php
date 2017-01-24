<?php
/**
 * Tagging plugin.
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Tagging
 */

$PluginInfo['Tagging'] = array(
    'Name' => 'Tagging',
    'Description' => 'Users may add tags to each discussion they create. Existing tags are shown in the sidebar for navigation by tag.',
    'Version' => '1.9.1',
    'SettingsUrl' => '/dashboard/settings/tagging',
    'UsePopupSettings' => false,
    'SettingsPermission' => 'Garden.Settings.Manage',
    'Author' => "Vanilla Staff",
    'AuthorEmail' => 'support@vanillaforums.com',
    'AuthorUrl' => 'https://open.vanillaforums.com',
    'MobileFriendly' => true,
    'RegisterPermissions' => array('Plugins.Tagging.Add' => 'Garden.Profiles.Edit'),
    'Icon' => 'tagging.png'
);

/**
 * Tagging Plugin
 *
 * Users may add tags to discussions as they're being created. Tags are shown
 * in the panel and on the OP.
 *
 * @changes
 *  1.5     Fix TagModule usage
 *  1.6     Add tag permissions
 *  1.6.1   Add tag permissions to UI
 *  1.7     Change the styling of special tags and prevent them from being edited/deleted.
 *  1.8     Add show existing tags
 *  1.8.4   Add tags before render so that other plugins can look at them.
 *  1.8.8   Added tabs.
 *  1.8.9   Ability to add tags based on tab.
 *  1.8.12  Fix issues with CSS and js loading.
 *  1.9.1   Add tokenLimit enforcement; cleanup.
 */
class TaggingPlugin extends Gdn_Plugin {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Add the Tagging admin menu option.
     */
    public function base_getAppSettingsMenuItems_handler($Sender) {
        $Menu = &$Sender->EventArguments['SideMenu'];
        $Menu->AddItem('Forum', t('Forum'));
        $Menu->addLink('Forum', t('Tagging'), 'settings/tagging', 'Garden.Settings.Manage');
    }

    /**
     * Search results for tagging autocomplete.
     *
     * @param $Sender
     * @param string $q
     * @param bool $id
     * @param bool $parent
     * @param string $type
     * @throws Exception
     */
    public function pluginController_tagSearch_create($Sender, $q = '', $id = false, $parent = false, $type = 'default') {

        // Allow per-category tags
        $CategorySearch = c('Plugins.Tagging.CategorySearch', false);
        if ($CategorySearch) {
            $CategoryID = GetIncomingValue('CategoryID');
        }

        if ($parent && !is_numeric($parent)) {
            $parent = Gdn::sql()->getWhere('Tag', array('Name' => $parent))->value('TagID', -1);
        }

        $Query = $q;
        $Data = array();
        $Database = Gdn::database();
        if ($Query || $parent || $type !== 'default') {
            $TagQuery = Gdn::sql()
                ->select('*')
                ->from('Tag')
                ->limit(20);

            if ($Query) {
                $TagQuery->like('FullName', str_replace(array('%', '_'), array('\%', '_'), $Query), strlen($Query) > 2 ? 'both' : 'right');
            }

            if ($type === 'default') {
                $defaultTypes = array_keys(TagModel::instance()->defaultTypes());
                $TagQuery->where('Type', $defaultTypes); // Other UIs can set a different type
            } elseif ($type) {
                $TagQuery->where('Type', $type);
            }

            // Allow per-category tags
            if ($CategorySearch) {
                $TagQuery->where('CategoryID', $CategoryID);
            }

            if ($parent) {
                $TagQuery->where('ParentTagID', $parent);
            }

            // Run tag search query
            $TagData = $TagQuery->get();

            foreach ($TagData as $Tag) {
                $Data[] = array('id' => $id ? $Tag->TagID : $Tag->Name, 'name' => $Tag->FullName);
            }
        }
        // Close the db before exiting.
        $Database->closeConnection();
        // Return the data
        header("Content-type: application/json");
        echo json_encode($Data);
        exit();
    }

    // SEEMS IT IS NOT USED
    /**
     *
     *
     * @param Gdn_SQLDriver $Sql
     * @param $Tag
     * @param $Limit
     * @param int $Offset
     * @param string $Op
     * @throws Exception
     */
    protected function _setTagSql($Sql, $Tag, &$Limit, &$Offset = 0, $Op = 'or') {
        $SortField = 'd.DateLastComment';
        $SortDirection = 'desc';

        $TagSql = clone Gdn::sql();

        if ($DateFrom = Gdn::request()->get('DateFrom')) {
            // Find the discussion ID of the first discussion created on or after the date from.
            $DiscussionIDFrom = $TagSql
                ->getWhere('Discussion', array('DateInserted >= ' => $DateFrom), 'DiscussionID', 'asc', 1)
                ->value('DiscussionID');
            $SortField = 'd.DiscussionID';
        }

        $Tags = array_map('trim', explode(',', $Tag));
        $TagIDs = $TagSql
            ->select('TagID')
            ->from('Tag')
            ->whereIn('Name', $Tags)
            ->get()->resultArray();

        $TagIDs = array_column($TagIDs, 'TagID');

        if ($Op == 'and' && count($Tags) > 1) {
            $DiscussionIDs = $TagSql
                ->select('DiscussionID')
                ->select('TagID', 'count', 'CountTags')
                ->from('TagDiscussion')
                ->whereIn('TagID', $TagIDs)
                ->groupBy('DiscussionID')
                ->having('CountTags >=', count($Tags))
                ->limit($Limit, $Offset)
                ->orderBy('DiscussionID', 'desc')
                ->get()->resultArray();
            $Limit = '';
            $Offset = 0;

            $DiscussionIDs = array_column($DiscussionIDs, 'DiscussionID');

            $Sql->whereIn('d.DiscussionID', $DiscussionIDs);
            $SortField = 'd.DiscussionID';
        } else {
            $Sql
                ->join('TagDiscussion td', 'd.DiscussionID = td.DiscussionID')
                ->limit($Limit, $Offset)
                ->whereIn('td.TagID', $TagIDs);

            if ($Op == 'and') {
                $SortField = 'd.DiscussionID';
            }
        }

        // Set up the sort field and direction.
        saveToConfig(
            array(
            'Vanilla.Discussions.SortField' => $SortField,
            'Vanilla.Discussions.SortDirection' => $SortDirection),
            '',
            false
        );
    }

    /**
     * List all tags and allow searching
     *
     * @param SettingsController $Sender
     */
    public function settingsController_tagging_create($Sender, $Search = null, $Type = null, $Page = null) {
        $Sender->title('Tagging');
        $Sender->setHighlightRoute('settings/tagging');
        $Sender->addJSFile('tagadmin.js', 'plugins/Tagging');
        $SQL = Gdn::sql();

        // Get all tag types
        $TagModel = TagModel::instance();
        $TagTypes = $TagModel->getTagTypes();

        $Sender->Form->Method = 'get';

        list($Offset, $Limit) = offsetLimit($Page, 100);
        $Sender->setData('_Limit', $Limit);

        if ($Search) {
            $SQL->like('Name', $Search, 'right');
        }

        $queryType = $Type;

        if (strtolower($Type) == 'all' || $Search || $Type === null) {
            $queryType = false;
            $Type = '';
        }

        // This type doesn't actually exist, but it will represent the
        // blank types in the column.
        if (strtolower($Type) == 'tags') {
            $queryType = '';
        }

        if (!$Search && ($queryType !== false)) {
            $SQL->where('Type', $queryType);
        }

        $TagTypes = array_change_key_case($TagTypes, CASE_LOWER);

        // Store type for view
        $TagType = (!empty($Type))
            ? $Type
            : 'All';
        $Sender->setData('_TagType', $TagType);

        // Store tag types
        $Sender->setData('_TagTypes', $TagTypes);

        // Determine if new tags can be added for the current type.
        $CanAddTags = (!empty($TagTypes[$Type]['addtag']) && $TagTypes[$Type]['addtag'])
            ? 1
            : 0;
        $CanAddTags &= CheckPermission('Plugins.Tagging.Add');

        $Sender->setData('_CanAddTags', $CanAddTags);

        $Data = $SQL
            ->select('t.*')
            ->from('Tag t')
            ->orderBy('t.CountDiscussions', 'desc')
            ->limit($Limit, $Offset)
            ->get()->resultArray();

        $Sender->setData('Tags', $Data);

        if ($Search) {
            $SQL->like('Name', $Search, 'right');
        }

        // Make sure search uses its own search type, so results appear
        // in their own tab.
        $Sender->Form->Action = url('/settings/tagging/?type='.$TagType);

        // Search results pagination will mess up a bit, so don't provide a type
        // in the count.
        $RecordCountWhere = array('Type' => $queryType);
        if ($queryType === false) {
            $RecordCountWhere = [];
        }
        if ($Search) {
            $RecordCountWhere = array();
        }

        $Sender->setData('RecordCount', $SQL->getCount('Tag', $RecordCountWhere));

        $Sender->render('tagging', '', 'plugins/Tagging');
    }


    /**
     * Add a Tag
     *
     * @param Gdn_Controller $Sender
     */
    public function controller_add($Sender) {
        $Sender->addSideMenu('settings/tagging');
        $Sender->title('Add Tag');

        // Set the model on the form.
        $TagModel = new TagModel;
        $Sender->Form->setModel($TagModel);

        // Add types if allowed to add tags for it, and not '' or 'tags', which
        // are the same.
        $TagType = Gdn::request()->get('type');
        if (strtolower($TagType) != 'tags'
            && $TagModel->canAddTagForType($TagType)
        ) {
            $Sender->Form->addHidden('Type', $TagType, true);
        }

        if ($Sender->Form->authenticatedPostBack()) {
            // Make sure the tag is valid
            $TagName = $Sender->Form->getFormValue('Name');
            if (!TagModel::validateTag($TagName)) {
                $Sender->Form->addError('@'.t('ValidateTag', 'Tags cannot contain commas.'));
            }

            $TagType = $Sender->Form->getFormValue('Type');
            if (!$TagModel->canAddTagForType($TagType)) {
                $Sender->Form->addError('@'.t('ValidateTagType', 'That type does not accept manually adding new tags.'));
            }

            // Make sure that the tag name is not already in use.
            if ($TagModel->getWhere(array('Name' => $TagName))->numRows() > 0) {
                $Sender->Form->addError('The specified tag name is already in use.');
            }

            $Saved = $Sender->Form->save();
            if ($Saved) {
                $Sender->informMessage(t('Your changes have been saved.'));
            }
        }

        $Sender->render('addedit', '', 'plugins/Tagging');
    }

    /**
     *
     *
     * @param $Sender
     * @return mixed
     * @throws Exception
     */
    public function settingsController_tags_create($Sender) {
        $Sender->permission('Garden.Settings.Manage');
        return $this->dispatch($Sender);
    }

    /**
     * Edit a Tag
     *
     * @param Gdn_Controller $Sender
     */
    public function controller_edit($Sender) {
        $Sender->addSideMenu('settings/tagging');
        $Sender->title(t('Edit Tag'));
        $TagID = val(1, $Sender->RequestArgs);

        // Set the model on the form.
        $TagModel = new TagModel;
        $Sender->Form->setModel($TagModel);
        $Tag = $TagModel->getID($TagID);
        $Sender->Form->setData($Tag);

        // Make sure the form knows which item we are editing.
        $Sender->Form->addHidden('TagID', $TagID);

        if ($Sender->Form->authenticatedPostBack()) {
            // Make sure the tag is valid
            $TagData = $Sender->Form->getFormValue('Name');
            if (!TagModel::validateTag($TagData)) {
                $Sender->Form->addError('@'.t('ValidateTag', 'Tags cannot contain commas.'));
            }

            // Make sure that the tag name is not already in use.
            if ($TagModel->getWhere(array('TagID <>' => $TagID, 'Name' => $TagData))->numRows() > 0) {
                $Sender->setData('MergeTagVisible', true);
                if (!$Sender->Form->getFormValue('MergeTag')) {
                    $Sender->Form->addError('The specified tag name is already in use.');
                }
            }

            if ($Sender->Form->Save()) {
                $Sender->informMessage(t('Your changes have been saved.'));
            }
        }

        $Sender->render('addedit', '', 'plugins/Tagging');
    }

    /**
     * Delete a Tag
     *
     * @param Gdn_Controller $Sender
     */
    public function controller_delete($Sender) {
        $Sender->permission('Garden.Settings.Manage');

        $TagID = val(1, $Sender->RequestArgs);
        $TagModel = new TagModel();
        $Tag = $TagModel->getID($TagID, DATASET_TYPE_ARRAY);
        if ($Sender->Form->authenticatedPostBack()) {
            // Delete tag & tag relations.
            $SQL = Gdn::sql();
            $SQL->delete('TagDiscussion', array('TagID' => $TagID));
            $SQL->delete('Tag', array('TagID' => $TagID));

            $Sender->informMessage(formatString(t('<b>{Name}</b> deleted.'), $Tag));
            $Sender->jsonTarget("#Tag_{$Tag['TagID']}", null, 'Remove');
        }

        $Sender->render('Blank', 'Utility', 'dashboard');
    }

    /**
     * Setup is called when the plugin is enabled.
     */
    public function setup() {
        $this->structure();
    }

    /**
     * Apply database structure updates
     */
    public function structure() {
        $PM = new PermissionModel();

        $PM->define(array(
            'Plugins.Tagging.Add' => 'Garden.Profiles.Edit'
        ));
    }
}

