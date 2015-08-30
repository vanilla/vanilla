<?php
/**
 * Tagging plugin.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Tagging
 */

$PluginInfo['Tagging'] = array(
    'Name' => 'Tagging',
    'Description' => 'Users may add tags to each discussion they create. Existing tags are shown in the sidebar for navigation by tag.',
    'Version' => '1.8.12',
    'SettingsUrl' => '/dashboard/settings/tagging',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'Author' => "Vanilla Staff",
    'AuthorEmail' => 'support@vanillaforums.com',
    'AuthorUrl' => 'http://vanillaforums.org',
    'MobileFriendly' => true,
    'RegisterPermissions' => array('Plugins.Tagging.Add' => 'Garden.Profiles.Edit')
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
     * Display the tag module in a category.
     */
    public function categoriesController_render_before($Sender) {
        $this->addTagModule($Sender);
    }

    /**
     * Add the tag admin page CSS.
     *
     * @param AssetModel $sender
     */
    public function assetModel_adminCss_handler($sender) {
        $sender->addCssFile('tagadmin.css', 'plugins/Tagging');
    }

    /**
     * Display the tag module in a discussion.
     */
    public function assetModel_styleCss_handler($Sender) {
        $Sender->addCSSFile('tag.css', 'plugins/Tagging');
        $Sender->addCSSFile('childtagslist.css', 'plugins/Tagging');
    }

    /**
     * Show tags after discussion body.
     */
    public function discussionController_afterDiscussionBody_handler($Sender) {
        // Allow disabling of inline tags.
        if (c('Plugins.Tagging.DisableInline', false)) {
            return;
        }

        if (!property_exists($Sender->EventArguments['Object'], 'CommentID')) {
            $DiscussionID = property_exists($Sender, 'DiscussionID') ? $Sender->DiscussionID : 0;

            if (!$DiscussionID) {
                return;
            }

            $TagModule = new TagModule($Sender);
            echo $TagModule->inlineDisplay();
        }
    }

    /**
     * @param DiscussionController $Sender
     */
    public function discussionController_render_before($Sender) {
        // Get the tags on this discussion.
        $Tags = TagModel::instance()->getDiscussionTags($Sender->data('Discussion.DiscussionID'), TagModel::IX_EXTENDED);

        foreach ($Tags as $Key => $Value) {
            setValue($Key, $Sender->Data['Discussion'], $Value);
        }
    }

    /**
     * Display the tag module on discussions lists.
     * @param DiscussionsController $Sender
     */
    public function discussionsController_render_before($Sender) {
        $this->addTagModule($Sender);
    }

    /**
     * Load discussions for a specific tag.
     * @param DiscussionsController $Sender
     */
    public function discussionsController_Tagged_create($Sender) {
        Gdn_Theme::section('DiscussionList');

        $Args = $Sender->RequestArgs;
        $Get = array_change_key_case($Sender->Request->get());

        if ($UseCategories = c('Plugins.Tagging.UseCategories')) {
            // The url is in the form /category/tag/p1
            $CategoryCode = val(0, $Args);
            $Tag = val(1, $Args);
            $Page = val(2, $Args);
        } else {
            // The url is in the form /tag/p1
            $CategoryCode = '';
            $Tag = val(0, $Args);
            $Page = val(1, $Args);
        }

        // Look for explcit values.
        $CategoryCode = val('category', $Get, $CategoryCode);
        $Tag = val('tag', $Get, $Tag);
        $Page = val('page', $Get, $Page);
        $Category = CategoryModel::categories($CategoryCode);

        $Tag = stringEndsWith($Tag, '.rss', true, true);
        list($Offset, $Limit) = offsetLimit($Page, c('Vanilla.Discussions.PerPage', 30));

        $MultipleTags = strpos($Tag, ',') !== false;

        $Sender->setData('Tag', $Tag, true);

        $TagModel = TagModel::instance();
        $RecordCount = false;
        if (!$MultipleTags) {
            $Tags = $TagModel->getWhere(array('Name' => $Tag))->resultArray();

            if (count($Tags) == 0) {
                throw notFoundException('Page');
            }

            if (count($Tags) > 1) {
                foreach ($Tags as $TagRow) {
                    if ($TagRow['CategoryID'] == val('CategoryID', $Category)) {
                        break;
                    }
                }
            } else {
                $TagRow = array_pop($Tags);
            }
            $Tags = $TagModel->getRelatedTags($TagRow);

            $RecordCount = $TagRow['CountDiscussions'];
            $Sender->setData('CountDiscussions', $RecordCount);
            $Sender->setData('Tags', $Tags);
            $Sender->setData('Tag', $TagRow);

            $ChildTags = $TagModel->getChildTags($TagRow['TagID']);
            $Sender->setData('ChildTags', $ChildTags);
        }

        $Sender->title(htmlspecialchars($TagRow['FullName']));
        $UrlTag = rawurlencode($Tag);
        if (urlencode($Tag) == $Tag) {
            $Sender->canonicalUrl(url(ConcatSep('/', "/discussions/tagged/$UrlTag", PageNumber($Offset, $Limit, true)), true));
            $FeedUrl = url(ConcatSep('/', "/discussions/tagged/$UrlTag/feed.rss", PageNumber($Offset, $Limit, true, false)), '//');
        } else {
            $Sender->canonicalUrl(url(ConcatSep('/', 'discussions/tagged', PageNumber($Offset, $Limit, true)).'?Tag='.$UrlTag, true));
            $FeedUrl = url(ConcatSep('/', 'discussions/tagged', PageNumber($Offset, $Limit, true, false), 'feed.rss').'?Tag='.$UrlTag, '//');
        }

        if ($Sender->Head) {
            $Sender->addJsFile('discussions.js');
            $Sender->Head->addRss($FeedUrl, $Sender->Head->title());
        }

        if (!is_numeric($Offset) || $Offset < 0) {
            $Offset = 0;
        }

        // Add Modules
        $Sender->addModule('NewDiscussionModule');
        $Sender->addModule('DiscussionFilterModule');
        $Sender->addModule('BookmarkedModule');

        $Sender->setData('Category', false, true);

        $Sender->AnnounceData = false;
        $Sender->setData('Announcements', array(), true);

        $DiscussionModel = new DiscussionModel();

        $TagModel->setTagSql($DiscussionModel->SQL, $Tag, $Limit, $Offset, $Sender->Request->get('op', 'or'));

        $Sender->DiscussionData = $DiscussionModel->get($Offset, $Limit, array('Announce' => 'all'));

        $Sender->setData('Discussions', $Sender->DiscussionData, true);
        $Sender->setJson('Loading', $Offset.' to '.$Limit);

        // Build a pager.
        $PagerFactory = new Gdn_PagerFactory();
        $Sender->Pager = $PagerFactory->GetPager('Pager', $Sender);
        $Sender->Pager->ClientID = 'Pager';
        $Sender->Pager->configure(
            $Offset,
            $Limit,
            $RecordCount, // record count
            ''
        );

        $Sender->View = c('Vanilla.Discussions.Layout');

        /*
        // If these don't equal, then there is a category that should be inserted.
        if ($UseCategories && $Category && $TagRow['FullName'] != val('Name', $Category)) {
           $Sender->Data['Breadcrumbs'][] = array('Name' => $Category['Name'], 'Url' => TagUrl($TagRow));
        }
        $Sender->Data['Breadcrumbs'][] = array('Name' => $TagRow['FullName'], 'Url' => '');
  */
        // Render the controller.
        $this->View = c('Vanilla.Discussions.Layout') == 'table' ? 'table' : 'index';
        $Sender->render($this->View, 'discussions', 'vanilla');
    }


    /**
     * Add tag breadcrumbs and tags data if appropriate.
     *
     * @param Gdn_Controller $Sender
     */
    public function base_render_before($Sender) {
        // Set breadcrumbs, where relevant.
        $this->setTagBreadcrumbs($Sender->Data);

        if (isset($Sender->Data['Announcements'])) {
            TagModel::instance()->joinTags($Sender->Data['Announcements']);
        }

        if (isset($Sender->Data['Discussions'])) {
            TagModel::instance()->joinTags($Sender->Data['Discussions']);
        }
    }

    /**
     * Create breadcrumbs for tag listings.
     *
     * @param object $data Sender->Data object
     */
    protected function setTagBreadcrumbs($data) {

        if (isset($data['Tag']) && isset($data['Tags'])) {
            $ParentTag = array();
            $CurrentTag = $data['Tag'];
            $CurrentTags = $data['Tags'];

            $ParentTagID = ($CurrentTag['ParentTagID'])
                ? $CurrentTag['ParentTagID']
                : '';

            foreach ($CurrentTags as $Tag) {
                foreach ($Tag as $SubTag) {
                    if ($SubTag['TagID'] == $ParentTagID) {
                        $ParentTag = $SubTag;
                    }
                }
            }

            $Breadcrumbs = array();

            if (is_array($ParentTag) && count(array_filter($ParentTag))) {
                $Breadcrumbs[] = array('Name' => $ParentTag['FullName'], 'Url' => TagUrl($ParentTag));
            }

            if (is_array($CurrentTag) && count(array_filter($CurrentTag))) {
                $Breadcrumbs[] = array('Name' => $CurrentTag['FullName'], 'Url' => TagUrl($CurrentTag));
            }

            if (count($Breadcrumbs)) {
                // Rebuild breadcrumbs in discussions when there is a child, as the
                // parent must come before it.
                Gdn::controller()->setData('Breadcrumbs', $Breadcrumbs);
            }

        }
    }

    /**
     * Save tags when saving a discussion.
     */
    public function discussionModel_afterSaveDiscussion_handler($Sender) {
        $FormPostValues = val('FormPostValues', $Sender->EventArguments, array());
        $DiscussionID = val('DiscussionID', $Sender->EventArguments, 0);
        $CategoryID = valr('Fields.CategoryID', $Sender->EventArguments, 0);
//      $IsInsert = val('Insert', $Sender->EventArguments);
        $RawFormTags = val('Tags', $FormPostValues, '');
        $FormTags = TagModel::splitTags($RawFormTags);

        // If we're associating with categories
        $CategorySearch = c('Plugins.Tagging.CategorySearch', false);
        if ($CategorySearch) {
            $CategoryID = val('CategoryID', $FormPostValues, false);
        }

        // Let plugins add their information getting saved.
        $Types = array('');
        $this->EventArguments['Data'] = $FormPostValues;
        $this->EventArguments['Tags'] =& $FormTags;
        $this->EventArguments['Types'] =& $Types;
        $this->EventArguments['CategoryID'] = $CategoryID;
        $this->fireEvent('SaveDiscussion');

        // Save the tags to the db.
        TagModel::instance()->saveDiscussion($DiscussionID, $FormTags, $Types, $CategoryID);
    }

    /**
     * Should we limit the discussion query to a specific tagid?
     * @param DiscussionModel $Sender
     */
//   public function DiscussionModel_BeforeGet_handler($Sender) {
//      if (c('Plugins.Tagging.Enabled') && property_exists($Sender, 'FilterToDiscussionIDs')) {
//         $Sender->SQL->whereIn('d.DiscussionID', $Sender->FilterToDiscussionIDs)
//            ->limit(FALSE);
//      }
//   }

    /**
     * Validate tags when saving a discussion.
     */
    public function discussionModel_beforeSaveDiscussion_handler($Sender, $Args) {
        $FormPostValues = val('FormPostValues', $Args, array());
        $TagsString = trim(strtolower(val('Tags', $FormPostValues, '')));
        $NumTagsMax = c('Plugin.Tagging.Max', 5);
        // Tags can only contain unicode and the following ASCII: a-z 0-9 + # _ .
        if (stringIsNullOrEmpty($TagsString) && c('Plugins.Tagging.Required')) {
            $Sender->Validation->addValidationResult('Tags', 'You must specify at least one tag.');
        } else {
            $Tags = TagModel::splitTags($TagsString);
            if (!TagModel::validateTags($Tags)) {
                $Sender->Validation->addValidationResult('Tags', '@'.t('ValidateTag', 'Tags cannot contain commas.'));
            } elseif (count($Tags) > $NumTagsMax) {
                $Sender->Validation->addValidationResult('Tags', '@'.sprintf(t('You can only specify up to %s tags.'), $NumTagsMax));
            } else {
            }
        }
    }

    /**
     *
     *
     * @param $Sender
     * @throws Exception
     */
    public function discussionModel_deleteDiscussion_handler($Sender) {
        // Get discussionID that is being deleted
        $DiscussionID = $Sender->EventArguments['DiscussionID'];

        // Get List of tags to reduce count for
        $TagDataSet = Gdn::sql()->select('TagID')
            ->from('TagDiscussion')
            ->where('DiscussionID', $DiscussionID)
            ->get()->resultArray();

        $RemovedTagIDs = array_column($TagDataSet, 'TagID');

        // Check if there are even any tags to delete
        if (count($RemovedTagIDs) > 0) {
            // Step 1: Reduce count
            Gdn::sql()
                ->update('Tag')
                ->set('CountDiscussions', 'CountDiscussions - 1', false)
                ->whereIn('TagID', $RemovedTagIDs)
                ->put();

            // Step 2: Delete mapping data between discussion and tag (tagdiscussion table)
            $Sender->SQL->where('DiscussionID', $DiscussionID)->delete('TagDiscussion');
        }
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
     * Add the tag input to the discussion form.
     *
     * @param Gdn_Controller $Sender
     */
    public function postController_afterDiscussionFormOptions_handler($Sender) {
        if (in_array($Sender->RequestMethod, array('discussion', 'editdiscussion', 'question'))) {
            // Setup, get most popular tags
            $TagModel = TagModel::instance();
            $Tags = $TagModel->getWhere(array('Type' => array_keys($TagModel->defaultTypes())), 'CountDiscussions', 'desc', c('Plugins.Tagging.ShowLimit', 50))->Result(DATASET_TYPE_ARRAY);
            $TagsHtml = (count($Tags)) ? '' : t('No tags have been created yet.');
            $Tags = Gdn_DataSet::index($Tags, 'FullName');
            ksort($Tags);

            // The tags must be fetched.
            if ($Sender->Request->isPostBack()) {
                $tag_ids = TagModel::SplitTags($Sender->Form->getFormValue('Tags'));
                $tags = TagModel::instance()->getWhere(array('TagID' => $tag_ids))->resultArray();
                $tags = array_column($tags, 'TagID', 'FullName');
            } else {
                // The tags should be set on the data.
                $tags = array_column($Sender->data('Tags', array()), 'FullName', 'TagID');
                $xtags = $Sender->data('XTags', array());
                foreach (TagModel::instance()->defaultTypes() as $key => $row) {
                    if (isset($xtags[$key])) {
                        $xtags2 = array_column($xtags[$key], 'FullName', 'TagID');
                        foreach ($xtags2 as $id => $name) {
                            $tags[$id] = $name;
                        }
                    }
                }
            }

            echo '<div class="Form-Tags P">';

            // Tag text box
            echo $Sender->Form->label('Tags', 'Tags');
            echo $Sender->Form->textBox('Tags', array('data-tags' => json_encode($tags)));

            // Available tags
            echo wrap(Anchor(t('Show popular tags'), '#'), 'span', array('class' => 'ShowTags'));
            foreach ($Tags as $Tag) {
                $TagsHtml .= anchor(htmlspecialchars($Tag['FullName']), '#', 'AvailableTag', array('data-name' => $Tag['Name'], 'data-id' => $Tag['TagID'])).' ';
            }
            echo wrap($TagsHtml, 'div', array('class' => 'Hidden AvailableTags'));

            echo '</div>';
        }
    }

    /**
     * Add javascript to the post/edit discussion page so that tagging autocomplete works.
     *
     * @param PostController $Sender
     */
    public function postController_render_before($Sender) {
        $Sender->addJsFile('jquery.tokeninput.js');
        $Sender->addJsFile('tagging.js', 'plugins/Tagging');
        $Sender->addDefinition('PluginsTaggingAdd', Gdn::session()->checkPermission('Plugins.Tagging.Add'));
        $Sender->addDefinition('PluginsTaggingSearchUrl', Gdn::request()->Url('plugin/tagsearch'));

        // Make sure that detailed tag data is available to the form.
        $TagModel = TagModel::instance();

        $DiscussionID = val('DiscussionID', $Sender->Data['Discussion']);

        if ($DiscussionID) {
            $Tags = $TagModel->getDiscussionTags($DiscussionID, TagModel::IX_EXTENDED);
            $Sender->setData($Tags);
        } elseif (!$Sender->Request->isPostBack() && $tagString = $Sender->Request->get('tags')) {
            $tags = explodeTrim(',', $tagString);
            $types = array_column(TagModel::instance()->defaultTypes(), 'key');

            // Look up the tags by name.
            $tagData = Gdn::sql()->getWhere(
                'Tag',
                array('Name' => $tags, 'Type' => $types)
            )->resultArray();

            // Add any missing tags.
            $tagNames = array_change_key_case(array_column($tagData, 'Name', 'Name'));
            foreach ($tags as $tag) {
                $tagKey = strtolower($tag);
                if (!isset($tagNames[$tagKey])) {
                    $tagData[] = array('TagID' => $tag, 'Name' => $tagKey, 'FullName' => $tag, 'Type' => '');
                }
            }

            $Sender->setData('Tags', $tagData);
        }
    }

    /**
     * List all tags and allow searching
     *
     * @param SettingsController $Sender
     */
    public function settingsController_tagging_create($Sender, $Search = null, $Type = null, $Page = null) {

        $Sender->title('Tagging');
        $Sender->addSideMenu('settings/tagging');
        $Sender->addJSFile('tagadmin.js', 'plugins/Tagging');
        $SQL = Gdn::sql();

        // Get all tag types
        $TagModel = TagModel::instance();
        $TagTypes = $TagModel->getTagTypes();

        $Sender->Form->Method = 'get';
        $Sender->Form->InputPrefix = '';

        list($Offset, $Limit) = offsetLimit($Page, 100);
        $Sender->setData('_Limit', $Limit);

        if ($Search) {
            $SQL->like('FullName', $Search, 'right');
        }

        // This type doesn't actually exist, but it will represent the
        // blank types in the column.
        if (strtolower($Type) == 'tags') {
            $Type = '';
        }

        if (!$Search) {
            if ($Type !== null) {
                if ($Type === 'null') {
                    $Type = null;
                }
                $SQL->where('Type', $Type);
            } elseif ($Type == '') {
                $SQL->where('Type', '');
            }
        } else {
            $Type = 'Search Results';
            // This is made up, and exists so search results can be placed in
            // their own tab.
            $TagTypes[$Type] = array(
                'key' => $Type
            );
        }

        $TagTypes = array_change_key_case($TagTypes, CASE_LOWER);

        // Store type for view
        $TagType = (!empty($Type))
            ? $Type
            : 'Tags';
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
            ->orderBy('t.FullName', 'asc')
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
        $RecordCountWhere = array('Type' => $Type);
        if ($Type == '') {
            $RecordCountWhere = array('Type' => '');
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

        $Sender->setData('Title', t('Delete Tag'));
        $Sender->render('delete', '', 'plugins/Tagging');
    }

    /**
     * Add update routines to the DBA controller
     *
     * @param DbaController $Sender
     */
    public function dbaController_countJobs_handler($Sender) {
        $Name = 'Recalculate Tag.CountDiscussions';
        $Url = "/dba/counts.json?".http_build_query(array('table' => 'Tag', 'column' => 'CountDiscussions'));
        $Sender->Data['Jobs'][$Name] = $Url;
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

    /**
     * Adds the tag module to the page.
     */
    private function addTagModule($Sender) {
        $TagModule = new TagModule($Sender);
        $Sender->addModule($TagModule);
    }
}

if (!function_exists('TagUrl')) :
    /**
     *
     *
     * @param $Row
     * @param string $Page
     * @param bool $WithDomain
     * @return string
     */
    function tagUrl($Row, $Page = '', $WithDomain = false) {
        static $UseCategories;
        if (!isset($UseCategories)) {
            $UseCategories = c('Plugins.Tagging.UseCategories');
        }

        // Add the p before a numeric page.
        if (is_numeric($Page)) {
            if ($Page > 1) {
                $Page = 'p'.$Page;
            } else {
                $Page = '';
            }
        }
        if ($Page) {
            $Page = '/'.$Page;
        }

        $Tag = rawurlencode(val('Name', $Row));

        if ($UseCategories) {
            $Category = CategoryModel::categories($Row['CategoryID']);
            if ($Category && $Category['CategoryID'] > 0) {
                $Category = rawurlencode(val('UrlCode', $Category, 'x'));
            } else {
                $Category = 'x';
            }
            $Result = "/discussions/tagged/$Category/$Tag{$Page}";
        } else {
            $Result = "/discussions/tagged/$Tag{$Page}";
        }

        return url($Result, $WithDomain);
    }
endif;

if (!function_exists('TagFullName')) :
    /**
     *
     *
     * @param $Row
     * @return mixed
     */
    function tagFullName($Row) {
        $Result = val('FullName', $Row);
        if (!$Result) {
            $Result = val('Name', $Row);
        }
        return $Result;
    }

endif;
