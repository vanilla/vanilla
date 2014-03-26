<?php if (!defined('APPLICATION')) exit();

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
 *
 * @author Mark O'Sullivan <mark@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Misc
 */

$PluginInfo['Tagging'] = array(
   'Name' => 'Tagging',
   'Description' => 'Users may add tags to each discussion they create. Existing tags are shown in the sidebar for navigation by tag.',
   'Version' => '1.8.7',
   'SettingsUrl' => '/dashboard/settings/tagging',
   'SettingsPermission' => 'Garden.Settings.Manage',
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://markosullivan.ca',
   'MobileFriendly' => true,
   'RegisterPermissions' => array('Plugins.Tagging.Add' => 'Garden.Profiles.Edit')
);

class TaggingPlugin extends Gdn_Plugin {

   public function __construct() {
      parent::__construct();
   }

   /**
    * Add the Tagging admin menu option.
    */
   public function Base_GetAppSettingsMenuItems_Handler($Sender) {
      $Menu = &$Sender->EventArguments['SideMenu'];
      $Menu->AddItem('Forum', T('Forum'));
      $Menu->AddLink('Forum', T('Tagging'), 'settings/tagging', 'Garden.Settings.Manage');
   }

   /**
    * Display the tag module in a category.
    */
   public function CategoriesController_Render_Before($Sender) {
      $this->AddTagModule($Sender);
   }

   /**
    * Display the tag module in a discussion.
    */
   public function AssetModel_StyleCss_Handler($Sender) {
      $Sender->AddCSSFile('tag.css', 'plugins/Tagging');
      $Sender->AddCSSFile('childtagslist.css', 'plugins/Tagging');
   }

   /**
    * Show tags after discussion body.
    */
   public function DiscussionController_AfterDiscussionBody_Handler($Sender) {
      // Allow disabling of inline tags.
      if (C('Plugins.Tagging.DisableInline', FALSE))
         return;

      if (!property_exists($Sender->EventArguments['Object'], 'CommentID')) {
         $DiscussionID = property_exists($Sender, 'DiscussionID') ? $Sender->DiscussionID : 0;

         if (!$DiscussionID)
            return;

         $TagModule = new TagModule($Sender);
         echo $TagModule->InlineDisplay();
      }
   }

   /**
    * @param DiscussionController $Sender
    */
   public function DiscussionController_Render_Before($Sender) {
      // Get the tags on this discussion.
      $Tags = TagModel::instance()->getDiscussionTags($Sender->Data('Discussion.DiscussionID'), TagModel::IX_EXTENDED);

      foreach ($Tags as $Key => $Value) {
         SetValue($Key, $Sender->Data['Discussion'], $Value);
      }
   }

   /**
    * Display the tag module on discussions lists.
    * @param DiscussionsController $Sender
    */
   public function DiscussionsController_Render_Before($Sender) {
      $this->AddTagModule($Sender);
   }

   /**
    * Load discussions for a specific tag.
    * @param DiscussionsController $Sender
    */
   public function DiscussionsController_Tagged_Create($Sender) {
      Gdn_Theme::Section('DiscussionList');

      $Args = $Sender->RequestArgs;
      $Get = array_change_key_case($Sender->Request->Get());

      if ($UseCategories = C('Plugins.Tagging.UseCategories')) {
         // The url is in the form /category/tag/p1
         $CategoryCode = GetValue(0, $Args);
         $Tag = GetValue(1, $Args);
         $Page = GetValue(2, $Args);
      } else {
         // The url is in the form /tag/p1
         $CategoryCode = '';
         $Tag = GetValue(0, $Args);
         $Page = GetValue(1, $Args);
      }

      // Look for explcit values.
      $CategoryCode = GetValue('category', $Get, $CategoryCode);
      $Tag = GetValue('tag', $Get, $Tag);
      $Page = GetValue('page', $Get, $Page);
      $Category = CategoryModel::Categories($CategoryCode);

      $Tag = StringEndsWith($Tag, '.rss', TRUE, TRUE);
      list($Offset, $Limit) = OffsetLimit($Page, C('Vanilla.Discussions.PerPage', 30));

      $MultipleTags = strpos($Tag, ',') !== FALSE;

      $Sender->SetData('Tag', $Tag, TRUE);

      $TagModel = TagModel::instance();
      $RecordCount = FALSE;
      if (!$MultipleTags) {
         $Tags = $TagModel->GetWhere(array('Name' => $Tag))->ResultArray();

         if (count($Tags) == 0) {
            throw NotFoundException('Page');
         }

         if (count($Tags) > 1) {
            foreach ($Tags as $TagRow) {
               if ($TagRow['CategoryID'] == GetValue('CategoryID', $Category)) {
                  break;
               }
            }
         } else {
            $TagRow = array_pop($Tags);
         }
         $Tags = $TagModel->getRelatedTags($TagRow);

         $RecordCount = $TagRow['CountDiscussions'];
         $Sender->SetData('CountDiscussions', $RecordCount);
         $Sender->SetData('Tags', $Tags);
         $Sender->SetData('Tag', $TagRow);

         $ChildTags = $TagModel->getChildTags($TagRow['TagID']);
         $Sender->SetData('ChildTags', $ChildTags);
      }

      $Sender->Title(htmlspecialchars($TagRow['FullName']));
      $UrlTag = rawurlencode($Tag);
      if (urlencode($Tag) == $Tag) {
         $Sender->CanonicalUrl(Url(ConcatSep('/', "/discussions/tagged/$UrlTag", PageNumber($Offset, $Limit, TRUE)), TRUE));
         $FeedUrl = Url(ConcatSep('/', "/discussions/tagged/$UrlTag/feed.rss", PageNumber($Offset, $Limit, TRUE, FALSE)), '//');
      } else {
         $Sender->CanonicalUrl(Url(ConcatSep('/', 'discussions/tagged', PageNumber($Offset, $Limit, TRUE)).'?Tag='.$UrlTag, TRUE));
         $FeedUrl = Url(ConcatSep('/', 'discussions/tagged', PageNumber($Offset, $Limit, TRUE, FALSE), 'feed.rss').'?Tag='.$UrlTag, '//');
      }

      if ($Sender->Head) {
         $Sender->AddJsFile('discussions.js');
         $Sender->Head->AddRss($FeedUrl, $Sender->Head->Title());
      }

      if (!is_numeric($Offset) || $Offset < 0)
         $Offset = 0;

      // Add Modules
      $Sender->AddModule('NewDiscussionModule');
      $Sender->AddModule('DiscussionFilterModule');
      $Sender->AddModule('BookmarkedModule');

      $Sender->SetData('Category', FALSE, TRUE);

      $Sender->AnnounceData = FALSE;
		$Sender->SetData('Announcements', array(), TRUE);

      $DiscussionModel = new DiscussionModel();

      $TagModel->SetTagSql($DiscussionModel->SQL, $Tag, $Limit, $Offset, $Sender->Request->Get('op', 'or'));

      $Sender->DiscussionData = $DiscussionModel->Get($Offset, $Limit, array('Announce' => 'all'));

      $Sender->SetData('Discussions', $Sender->DiscussionData, TRUE);
      $Sender->SetJson('Loading', $Offset . ' to ' . $Limit);

      // Build a pager.
      $PagerFactory = new Gdn_PagerFactory();
      $Sender->Pager = $PagerFactory->GetPager('Pager', $Sender);
      $Sender->Pager->ClientID = 'Pager';
      $Sender->Pager->Configure(
         $Offset,
         $Limit,
         $RecordCount, // record count
         ''
      );

      $Sender->View = C('Vanilla.Discussions.Layout');

      /*
      // If these don't equal, then there is a category that should be inserted.
      if ($UseCategories && $Category && $TagRow['FullName'] != GetValue('Name', $Category)) {
         $Sender->Data['Breadcrumbs'][] = array('Name' => $Category['Name'], 'Url' => TagUrl($TagRow));
      }
      $Sender->Data['Breadcrumbs'][] = array('Name' => $TagRow['FullName'], 'Url' => '');
*/
      // Render the controller.
      $this->View = C('Vanilla.Discussions.Layout') == 'table' ? 'table' : 'index';
      $Sender->Render($this->View, 'discussions', 'vanilla');
   }


   /**
    * Add tag breadcrumbs and tags data if appropriate.
    *
    * @param Gdn_Controller $Sender
    */
   public function Base_Render_Before($Sender) {
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

         foreach($CurrentTags as $Tag) {
            foreach($Tag as $SubTag) {
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
            Gdn::Controller()->SetData('Breadcrumbs', $Breadcrumbs);
         }

      }
   }

   /**
    * Save tags when saving a discussion.
    */
   public function DiscussionModel_AfterSaveDiscussion_Handler($Sender) {
      $FormPostValues = GetValue('FormPostValues', $Sender->EventArguments, array());
      $DiscussionID = GetValue('DiscussionID', $Sender->EventArguments, 0);
      $CategoryID = GetValueR('Fields.CategoryID', $Sender->EventArguments, 0);
//      $IsInsert = GetValue('Insert', $Sender->EventArguments);
      $RawFormTags = GetValue('Tags', $FormPostValues, '');
      $FormTags = trim(strtolower($RawFormTags));
      $FormTags = TagModel::SplitTags($FormTags);

      // If we're associating with categories
      $CategorySearch = C('Plugins.Tagging.CategorySearch', FALSE);
      if ($CategorySearch)
         $CategoryID = GetValue('CategoryID', $FormPostValues, FALSE);

      // Let plugins add their information getting saved.
      $Types = array('');
      $this->EventArguments['Data'] = $FormPostValues;
      $this->EventArguments['Tags'] =& $FormTags;
      $this->EventArguments['Types'] =& $Types;
      $this->EventArguments['CategoryID'] = $CategoryID;
      $this->FireEvent('SaveDiscussion');

      // Save the tags to the db.
      TagModel::instance()->saveDiscussion($DiscussionID, $FormTags, $Types, $CategoryID);
   }

   /**
    * Should we limit the discussion query to a specific tagid?
    * @param DiscussionModel $Sender
    */
//   public function DiscussionModel_BeforeGet_Handler($Sender) {
//      if (C('Plugins.Tagging.Enabled') && property_exists($Sender, 'FilterToDiscussionIDs')) {
//         $Sender->SQL->WhereIn('d.DiscussionID', $Sender->FilterToDiscussionIDs)
//            ->Limit(FALSE);
//      }
//   }

   /**
    * Validate tags when saving a discussion.
    */
   public function DiscussionModel_BeforeSaveDiscussion_Handler($Sender, $Args) {
      $FormPostValues = GetValue('FormPostValues', $Args, array());
      $TagsString = trim(strtolower(GetValue('Tags', $FormPostValues, '')));
      $NumTagsMax = C('Plugin.Tagging.Max', 5);
      // Tags can only contain unicode and the following ASCII: a-z 0-9 + # _ .
      if (StringIsNullOrEmpty($TagsString) && C('Plugins.Tagging.Required')) {
         $Sender->Validation->AddValidationResult('Tags', 'You must specify at least one tag.');
      } else {
         $Tags = TagModel::SplitTags($TagsString);
         if (!TagModel::ValidateTags($Tags)) {
            $Sender->Validation->AddValidationResult('Tags', '@'.T('ValidateTag', 'Tags cannot contain commas.'));
         } elseif (count($Tags) > $NumTagsMax) {
            $Sender->Validation->AddValidationResult('Tags', '@'.sprintf(T('You can only specify up to %s tags.'), $NumTagsMax));
         } else {

         }
      }
   }

   public function DiscussionModel_DeleteDiscussion_Handler($Sender) {
      // Get discussionID that is being deleted
      $DiscussionID = $Sender->EventArguments['DiscussionID'];

      // Get List of tags to reduce count for
      $TagDataSet = Gdn::SQL()->Select('TagID')
         ->From('TagDiscussion')
         ->Where('DiscussionID',$DiscussionID)
         ->Get()->ResultArray();

      $RemovedTagIDs = ConsolidateArrayValuesByKey($TagDataSet, 'TagID');

      // Check if there are even any tags to delete
      if (count($RemovedTagIDs) > 0) {
         // Step 1: Reduce count
         Gdn::SQL()->Update('Tag')->Set('CountDiscussions', 'CountDiscussions - 1', FALSE)->WhereIn('TagID', $RemovedTagIDs)->Put();

         // Step 2: Delete mapping data between discussion and tag (tagdiscussion table)
         $Sender->SQL->Where('DiscussionID', $DiscussionID)->Delete('TagDiscussion');
      }
   }

   /**
    * Search results for tagging autocomplete.
    */
   public function PluginController_TagSearch_Create($Sender, $q = '', $id = false, $parent = false, $type = 'default') {

      // Allow per-category tags
      $CategorySearch = C('Plugins.Tagging.CategorySearch', FALSE);
      if ($CategorySearch)
         $CategoryID = GetIncomingValue('CategoryID');

      if ($parent && !is_numeric($parent))
         $parent = Gdn::SQL()->GetWhere('Tag', array('Name' => $parent))->Value('TagID', -1);

      $Query = $q;
      $Data = array();
      $Database = Gdn::Database();
      if ($Query || $parent || $type !== 'default') {
         $TagQuery = Gdn::SQL()
            ->Select('*')
            ->From('Tag')
            ->Limit(20);

         if ($Query) {
            $TagQuery->Like('FullName', str_replace(array('%', '_'), array('\%', '_'), $Query), strlen($Query) > 2 ? 'both' : 'right');
         }

         if ($type === 'default') {
            $defaultTypes = array_keys(TagModel::instance()->defaultTypes());
            $TagQuery->Where('Type', $defaultTypes); // Other UIs can set a different type
         } elseif ($type) {
            $TagQuery->Where('Type', $type);
         }

         // Allow per-category tags
         if ($CategorySearch)
            $TagQuery->Where('CategoryID', $CategoryID);

         if ($parent) {
            $TagQuery->Where('ParentTagID', $parent);
         }

         // Run tag search query
         $TagData = $TagQuery->Get();

         foreach ($TagData as $Tag) {
            $Data[] = array('id' => $id ? $Tag->TagID : $Tag->Name, 'name' => $Tag->FullName);
         }
      }
      // Close the db before exiting.
      $Database->CloseConnection();
      // Return the data
      header("Content-type: application/json");
      echo json_encode($Data);
      exit();
   }

   /**
    *
    * @param Gdn_SQLDriver $Sql
    */
   protected function _SetTagSql($Sql, $Tag, &$Limit, &$Offset = 0, $Op = 'or') {
      $SortField = 'd.DateLastComment';
      $SortDirection = 'desc';

      $TagSql = clone Gdn::Sql();

      if ($DateFrom = Gdn::Request()->Get('DateFrom')) {
         // Find the discussion ID of the first discussion created on or after the date from.
         $DiscussionIDFrom = $TagSql->GetWhere('Discussion', array('DateInserted >= ' => $DateFrom), 'DiscussionID', 'asc', 1)->Value('DiscussionID');
         $SortField = 'd.DiscussionID';
      }

      $Tags = array_map('trim', explode(',', $Tag));
      $TagIDs = $TagSql
         ->Select('TagID')
         ->From('Tag')
         ->WhereIn('Name', $Tags)
         ->Get()->ResultArray();

      $TagIDs = ConsolidateArrayValuesByKey($TagIDs, 'TagID');

      if ($Op == 'and' && count($Tags) > 1) {
         $DiscussionIDs = $TagSql
            ->Select('DiscussionID')
            ->Select('TagID', 'count', 'CountTags')
            ->From('TagDiscussion')
            ->WhereIn('TagID', $TagIDs)
            ->GroupBy('DiscussionID')
            ->Having('CountTags >=', count($Tags))
            ->Limit($Limit, $Offset)
            ->OrderBy('DiscussionID', 'desc')
            ->Get()->ResultArray();
         $Limit = '';
         $Offset = 0;

         $DiscussionIDs = ConsolidateArrayValuesByKey($DiscussionIDs, 'DiscussionID');

         $Sql->WhereIn('d.DiscussionID', $DiscussionIDs);
         $SortField = 'd.DiscussionID';
      } else {
         $Sql
            ->Join('TagDiscussion td', 'd.DiscussionID = td.DiscussionID')
            ->Limit($Limit, $Offset)
            ->WhereIn('td.TagID', $TagIDs);

         if ($Op == 'and')
            $SortField = 'd.DiscussionID';
      }

      // Set up the sort field and direction.
      SaveToConfig(array(
          'Vanilla.Discussions.SortField' => $SortField,
          'Vanilla.Discussions.SortDirection' => $SortDirection),
          '',
          FALSE);
   }

   /**
    * Add the tag input to the discussion form.
    * @param Gdn_Controller $Sender
    */
   public function PostController_AfterDiscussionFormOptions_Handler($Sender) {
      if (in_array($Sender->RequestMethod, array('discussion', 'editdiscussion', 'question'))) {
         // Setup, get most popular tags
         $TagModel = TagModel::instance();
         $Tags = $TagModel->GetWhere(array('Type' => array_keys($TagModel->defaultTypes())), 'CountDiscussions', 'desc', C('Plugins.Tagging.ShowLimit', 50))->Result(DATASET_TYPE_ARRAY);
         $TagsHtml = (count($Tags)) ? '' : T('No tags have been created yet.');
         $Tags = Gdn_DataSet::Index($Tags, 'FullName');
         ksort($Tags);

         // The tags must be fetched.
         if ($Sender->Request->IsPostBack()) {
            $tag_ids = TagModel::SplitTags($Sender->Form->GetFormValue('Tags'));
            $tags = TagModel::instance()->GetWhere(array('TagID' => $tag_ids))->ResultArray();
            $tags = ConsolidateArrayValuesByKey($tags, 'FullName', 'TagID');
         } else {
            // The tags should be set on the data.
            $tags = ConsolidateArrayValuesByKey($Sender->Data('Tags', array()), 'TagID', 'FullName');
            $xtags = $Sender->Data('XTags', array());
            foreach (TagModel::instance()->defaultTypes() as $key => $row) {
               if (isset($xtags[$key])) {
                  $xtags2 = ConsolidateArrayValuesByKey($xtags[$key], 'TagID', 'FullName');
                  foreach ($xtags2 as $id => $name) {
                     $tags[$id] = $name;
                  }
               }
            }
         }

         echo '<div class="Form-Tags P">';

         // Tag text box
         echo $Sender->Form->Label('Tags', 'Tags');
         echo $Sender->Form->TextBox('Tags', array('data-tags' => json_encode($tags)));

         // Available tags
         echo Wrap(Anchor(T('Show popular tags'), '#'), 'span', array('class' => 'ShowTags'));
         foreach ($Tags as $Tag) {
            $TagsHtml .= Anchor(htmlspecialchars($Tag['FullName']), '#', 'AvailableTag', array('data-name' => $Tag['Name'], 'data-id' => $Tag['TagID'])).' ';
         }
         echo Wrap($TagsHtml, 'div', array('class' => 'Hidden AvailableTags'));

         echo '</div>';
      }
   }

   /**
    * Add javascript to the post/edit discussion page so that tagging autocomplete works.
    */
   public function PostController_Render_Before($Sender) {
      $Sender->AddJsFile('jquery.tokeninput.js');
      $Sender->AddJsFile('tagging.js', 'plugins/Tagging');
      $Sender->AddDefinition('PluginsTaggingAdd', Gdn::Session()->CheckPermission('Plugins.Tagging.Add'));
      $Sender->AddDefinition('PluginsTaggingSearchUrl', Gdn::Request()->Url('plugin/tagsearch'));

      // Make sure that detailed tag data is available to the form.
      $DiscussionID = GetValue('DiscussionID', $Sender->Data['Discussion']);
      $TagModel = TagModel::instance();
      $Tags = $TagModel->getDiscussionTags($DiscussionID, TagModel::IX_EXTENDED);
      $Sender->SetData($Tags);
   }

   /**
    * List all tags and allow searching
    *
    * @param SettingsController $Sender
    */
   public function SettingsController_Tagging_Create($Sender, $Search = NULL, $Type = NULL, $Page = NULL) {

      $Sender->Title('Tagging');
      $Sender->AddSideMenu('settings/tagging');
      $Sender->AddCSSFile('plugins/Tagging/design/tagadmin.css');
      $Sender->AddJSFile('plugins/Tagging/js/admin.js');
      $SQL = Gdn::SQL();

      $Sender->Form->Method = 'get';
      $Sender->Form->InputPrefix = '';
      //$Sender->Form->Action = '/settings/tagging';

      list($Offset, $Limit) = OffsetLimit($Page, 100);
      $Sender->SetData('_Limit', $Limit);

      if ($Search) {
         $SQL->Like('FullName', $Search , 'right');
      }
      if ($Type !== NULL) {
         if ($Type === 'null')
            $Type = NULL;
         $SQL->Where('Type', $Type);
      }

      $Data = $SQL
         ->Select('t.*')
         ->From('Tag t')
         ->OrderBy('t.FullName', 'asc')
         ->OrderBy('t.CountDiscussions', 'desc')
         ->Limit($Limit, $Offset)
         ->Get()->ResultArray();

      $Sender->SetData('Tags', $Data);

      if ($Search = $Sender->Request->Get('Search')) {
         $SQL->Like('Name', $Search , 'right');
      }
      $Sender->SetData('RecordCount', $SQL->GetCount('Tag'));

      $Sender->Render('tagging', '', 'plugins/Tagging');
   }



   /**
    * Add a Tag
    *
    * @param Gdn_Controller $Sender
    */
   public function Controller_Add($Sender) {
      $Sender->AddSideMenu('settings/tagging');
      $Sender->Title('Add Tag');

      // Set the model on the form.
      $TagModel = new TagModel;
      $Sender->Form->SetModel($TagModel);

      if ($Sender->Form->AuthenticatedPostBack()) {
         // Make sure the tag is valid
         $TagName = $Sender->Form->GetFormValue('Name');
         if (!TagModel::ValidateTag($TagName))
            $Sender->Form->AddError('@'.T('ValidateTag', 'Tags cannot contain commas.'));

         // Make sure that the tag name is not already in use.
         if ($TagModel->GetWhere(array('Name' => $TagName))->NumRows() > 0) {
            $Sender->Form->AddError('The specified tag name is already in use.');
         }

         $Saved = $Sender->Form->Save();
         if ($Saved)
            $Sender->InformMessage(T('Your changes have been saved.'));
      }

      $Sender->Render('addedit', '', 'plugins/Tagging');
   }

   public function SettingsController_Tags_Create($Sender) {
      $Sender->Permission('Garden.Settings.Manage');
      return $this->Dispatch($Sender);
   }

   /**
    * Edit a Tag
    *
    * @param Gdn_Controller $Sender
    */
   public function Controller_Edit($Sender) {
      $Sender->AddSideMenu('settings/tagging');
      $Sender->Title(T('Edit Tag'));
      $TagID = GetValue(1, $Sender->RequestArgs);

      // Set the model on the form.
      $TagModel = new TagModel;
      $Sender->Form->SetModel($TagModel);
      $Tag = $TagModel->GetID($TagID);
      $Sender->Form->SetData($Tag);

      // Make sure the form knows which item we are editing.
      $Sender->Form->AddHidden('TagID', $TagID);

      if ($Sender->Form->AuthenticatedPostBack()) {
         // Make sure the tag is valid
         $TagData = $Sender->Form->GetFormValue('Name');
         if (!TagModel::ValidateTag($TagData))
            $Sender->Form->AddError('@'.T('ValidateTag', 'Tags cannot contain commas.'));

         // Make sure that the tag name is not already in use.
         if ($TagModel->GetWhere(array('TagID <>' => $TagID, 'Name' => $TagData))->NumRows() > 0) {
            $Sender->SetData('MergeTagVisible', TRUE);
            if (!$Sender->Form->GetFormValue('MergeTag')) {
               $Sender->Form->AddError('The specified tag name is already in use.');
            }
         }

         if ($Sender->Form->Save())
            $Sender->InformMessage(T('Your changes have been saved.'));
      }

      $Sender->Render('addedit', '', 'plugins/Tagging');
   }

   /**
    * Delete a Tag
    *
    * @param Gdn_Controller $Sender
    */
   public function Controller_Delete($Sender) {
      $Sender->Permission('Garden.Settings.Manage');

      $TagID = GetValue(1, $Sender->RequestArgs);
      $TagModel = new TagModel();
      $Tag = $TagModel->GetID($TagID, DATASET_TYPE_ARRAY);
      if ($Sender->Form->AuthenticatedPostBack()) {
         // Delete tag & tag relations.
         $SQL = Gdn::SQL();
         $SQL->Delete('TagDiscussion', array('TagID' => $TagID));
         $SQL->Delete('Tag', array('TagID' => $TagID));

         $Sender->InformMessage(FormatString(T('<b>{Name}</b> deleted.'), $Tag));
         $Sender->JsonTarget("#Tag_{$Tag['TagID']}", NULL, 'Remove');
      }

      $Sender->SetData('Title', T('Delete Tag'));
      $Sender->Render('delete', '', 'plugins/Tagging');
   }

   /**
    * Setup is called when the plugin is enabled.
    */
   public function Setup() {
      $this->Structure();
   }

   /**
    * Apply database structure updates
    */
   public function Structure() {
      $PM = new PermissionModel();

      $PM->Define(array(
         'Plugins.Tagging.Add' => 'Garden.Profiles.Edit'
      ));
   }

   /**
    * Adds the tag module to the page.
    */
   private function AddTagModule($Sender) {
      $TagModule = new TagModule($Sender);
      $Sender->AddModule($TagModule);
   }

}

if (!function_exists('TagUrl')):
   function TagUrl($Row, $Page = '', $WithDomain = FALSE) {
      static $UseCategories;
      if (!isset($UseCategories))
         $UseCategories = C('Plugins.Tagging.UseCategories');

      // Add the p before a numeric page.
      if (is_numeric($Page)) {
         if ($Page > 1)
            $Page = 'p'.$Page;
         else
            $Page = '';
      }
      if ($Page) {
         $Page = '/'.$Page;
      }

      $Tag = rawurlencode(GetValue('Name', $Row));

      if ($UseCategories) {
         $Category = CategoryModel::Categories($Row['CategoryID']);
         if ($Category && $Category['CategoryID'] > 0)
            $Category = rawurlencode(GetValue('UrlCode', $Category, 'x'));
         else
            $Category = 'x';
         $Result = "/discussions/tagged/$Category/$Tag{$Page}";
      } else {
         $Result = "/discussions/tagged/$Tag{$Page}";
      }

      return Url($Result, $WithDomain);
   }
endif;

if (!function_exists('TagFullName')):

function TagFullName($Row) {
   $Result = GetValue('FullName', $Row);
   if (!$Result)
      $Result = GetValue('Name', $Row);
   return $Result;
}

endif;
