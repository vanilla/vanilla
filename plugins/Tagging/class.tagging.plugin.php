<?php if (!defined('APPLICATION')) exit();

/**
 * Tagging Plugin
 * 
 * Users may add tags to discussions as they're being created. Tags are shown
 * in the panel and on the OP.
 * 
 * Changes: 
 *  1.5     Fix TagModule usage
 *  1.6     Add tag permissions
 *  1.6.1   Add tag permissions to UI
 * 
 * @author Mark O'Sullivan <mark@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Misc
 */

$PluginInfo['Tagging'] = array(
   'Name' => 'Tagging',
   'Description' => 'Users may add tags to each discussion they create. Existing tags are shown in the sidebar for navigation by tag.',
   'Version' => '1.6.2',
   'SettingsUrl' => '/dashboard/settings/tagging',
   'SettingsPermission' => 'Garden.Settings.Manage',
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://markosullivan.ca',
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
    * Display the tag module on discussions lists.
    * @param DiscussionsController $Sender
    */
   public function DiscussionsController_Render_Before($Sender) {
      $this->AddTagModule($Sender);
   }

   /**
    * Load discussions for a specific tag.
    */
   public function DiscussionsController_Tagged_Create($Sender) {
      Gdn_Theme::Section('DiscussionList');
      
      if ($Sender->Request->Get('Tag')) {
         $Tag = $Sender->Request->Get('Tag');
         $Page = GetValue('0', $Sender->RequestArgs, 'p1');
      } else {
         $Tag = urldecode(GetValue('0', $Sender->RequestArgs, ''));
         $Page = GetValue('1', $Sender->RequestArgs, 'p1');
      }
      
      if ($Sender->Request->Get('Page')) {
         $Page = $Sender->Request->Get('Page');
      }
      
      $Tag = StringEndsWith($Tag, '.rss', TRUE, TRUE);
      list($Offset, $Limit) = OffsetLimit($Page, C('Vanilla.Discussions.PerPage', 30));
   
      $Sender->SetData('Tag', $Tag, TRUE);
      $Sender->Title(T('Tagged with ').htmlspecialchars($Tag));
      $Sender->Head->Title($Sender->Head->Title());
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
      $BookmarkedModule = new BookmarkedModule($Sender);
      $BookmarkedModule->GetData();
      $Sender->AddModule($BookmarkedModule);

      $Sender->SetData('Category', FALSE, TRUE);
      $Sender->SetData('CountDiscussions', FALSE);
      
      $Sender->AnnounceData = FALSE;
		$Sender->SetData('Announcements', array(), TRUE);
      
      $DiscussionModel = new DiscussionModel();
      $this->_SetTagSql($DiscussionModel->SQL, $Tag, $Limit, $Offset, $Sender->Request->Get('op', 'or'));
      $Sender->DiscussionData = $DiscussionModel->Get($Offset, $Limit, array('Announce' => 'all'));
      
      $Sender->SetData('Discussions', $Sender->DiscussionData, TRUE);
      $Sender->SetJson('Loading', $Offset . ' to ' . $Limit);

      // Build a pager.
      $PagerFactory = new Gdn_PagerFactory();
      $Sender->Pager = $PagerFactory->GetPager('Pager', $Sender);
      $Sender->Pager->ClientID = 'Pager';

      if (urlencode($Sender->Tag) == $Sender->Tag)
         $PageUrlFormat = "discussions/tagged/{$Sender->Tag}/{Page}";
      else
         $PageUrlFormat = 'discussions/tagged/{Page}?Tag='.urlencode($Sender->Tag);

      $Sender->Pager->Configure(
         $Offset,
         $Limit,
         FALSE,
         $PageUrlFormat
      );
      
      // Deliver json data if necessary.
      if ($Sender->DeliveryType() != DELIVERY_TYPE_ALL) {
         $Sender->SetJson('LessRow', $Sender->Pager->ToString('less'));
         $Sender->SetJson('MoreRow', $Sender->Pager->ToString('more'));
         $Sender->View = 'discussions';
      }
      
      // Render the controller
      $Sender->Render('TaggedDiscussions', '', 'plugins/Tagging');
   }
   
   /**
    * Save tags when saving a discussion.
    */
   public function DiscussionModel_AfterSaveDiscussion_Handler($Sender) {
      $FormPostValues = GetValue('FormPostValues', $Sender->EventArguments, array());
      $DiscussionID = GetValue('DiscussionID', $Sender->EventArguments, 0);
      $CategoryID = GetValueR('Fields.CategoryID', $Sender->EventArguments, 0);
      $IsInsert = GetValue('Insert', $Sender->EventArguments);
      $RawFormTags = GetValue('Tags', $FormPostValues, '');
      $FormTags = trim(strtolower($RawFormTags));
      $FormTags = TagModel::SplitTags($FormTags);
      
      // Get discussion
      
      // If we're associating with categories
      $CategorySearch = C('Plugins.Tagging.CategorySearch', FALSE);
      if ($CategorySearch)
         $CategoryID = GetValue('CategoryID', $FormPostValues, FALSE);
      
      // Resave the Discussion's Tags field as serialized
      $SerializedTags = Gdn_Format::Serialize(explode(',',$RawFormTags));
      $Sender->SQL->Update('Discussion')->Set('Tags', $SerializedTags)->Where('DiscussionID', $DiscussionID)->Put();
      
      // Find out which of these tags is not yet in the tag table
      $ExistingTagQuery = $Sender->SQL->Select('TagID, Name')
         ->From('Tag')
         ->WhereIn('Name', $FormTags);
      
      if ($CategorySearch)
         $ExistingTagQuery->Where('CategoryID', $CategoryID);
      
      $ExistingTagData = $ExistingTagQuery->Get();
      $NewTags = $FormTags;
      $Tags = array(); // <-- Build a complete associative array of $Tags[TagID] => TagName values for this discussion.
      foreach ($ExistingTagData as $ExistingTag) {
         if (in_array($ExistingTag->Name, $NewTags))
            unset($NewTags[array_search($ExistingTag->Name, $NewTags)]);

         $Tags[$ExistingTag->TagID] = $ExistingTag->Name;
      }

      // Insert the missing ones (if we have permission)
      if (Gdn::Session()->CheckPermission('Plugins.Tagging.Add')) {
         foreach ($NewTags as $NewTag) {

            $NewTag = array(
               'Name' => strtolower($NewTag),
               'InsertUserID' => Gdn::Session()->UserID,
               'DateInserted' => Gdn_Format::ToDateTime(),
               'CountDiscussions' => 0
            );

            if ($CategorySearch)
               $NewTag['CategoryID'] = $CategoryID;

            $TagID = $Sender->SQL->Insert('Tag', $NewTag);
            $Tags[$TagID] = $NewTag;
         }
      }

      // Find out which tags are not yet associated with this discussion, and which tags are no longer on this discussion
      $TagIDs = array_keys($Tags);
      $NonAssociatedTagIDs = $TagIDs;
      $AssociatedTagIDs = array();
      $RemovedTagIDs = array();
      $ExistingTagData = $Sender->SQL
         ->Select('t.*')
         ->From('TagDiscussion td')
         ->Join('Tag t', 'td.TagID = t.TagID')
         ->Where('DiscussionID', $DiscussionID)
         ->Get();
      
      foreach ($ExistingTagData as $ExistingTag) {
         if (in_array($ExistingTag->TagID, $TagIDs))
            unset($NonAssociatedTagIDs[array_search($ExistingTag->TagID, $NonAssociatedTagIDs)]);
         else if (!GetValue('Type', $ExistingTag) && !in_array($ExistingTag->TagID, $TagIDs))
            $RemovedTagIDs[] = $ExistingTag->TagID;
         else
            $AssociatedTagIDs[] = $ExistingTag->TagID;
      }

      // Associate the ones that weren't already associated
      foreach ($NonAssociatedTagIDs as $TagID) {
         $Sender->SQL->Insert('TagDiscussion', array(
            'TagID' => $TagID,
            'DiscussionID' => $DiscussionID, 
            'CategoryID' => $CategoryID
         ));
      }

      // Remove tags that were removed, and reduce their counts
      if (count($RemovedTagIDs) > 0) {
         // Reduce count
         $Sender->SQL->Update('Tag')->Set('CountDiscussions', 'CountDiscussions - 1', FALSE)->WhereIn('TagID', $RemovedTagIDs)->Put();
         // Remove association
         $Sender->SQL->WhereIn('TagID', $RemovedTagIDs)->Delete('TagDiscussion', array('DiscussionID' => $DiscussionID));
      }

      // Update the count on all previously unassociated tags
      $Sender->SQL->Update('Tag')->Set('CountDiscussions', 'CountDiscussions + 1', FALSE)->WhereIn('TagID', $NonAssociatedTagIDs)->Put();
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
   public function PluginController_TagSearch_Create($Sender) {
      
      // Allow per-category tags
      $CategorySearch = C('Plugins.Tagging.CategorySearch', FALSE);
      if ($CategorySearch)
         $CategoryID = GetIncomingValue('CategoryID');
      
      $Query = GetIncomingValue('q');
      $Data = array();
      $Database = Gdn::Database();
      if ($Query) {
         $Test = Gdn::SQL()->Limit(1)->Get('Tag')->FirstRow(DATASET_TYPE_ARRAY);
         if (isset($Test['Type'])) {
            Gdn::SQL()->Where("nullif(Type, '') is null"); // Other UIs can set a different type
         }
         
         $TagQuery = Gdn::SQL()
            ->Select('TagID, Name')
            ->From('Tag')
            ->Like('Name', $Query)
            ->Limit(20);
         
         // Allow per-category tags
         if ($CategorySearch)
            $TagQuery->Where('CategoryID', $CategoryID);
         
         // Run tag search query
         $TagData = $TagQuery->Get();
         
         foreach ($TagData as $Tag) {
            $Data[] = array('id' => $Tag->Name, 'name' => $Tag->Name);
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
         echo '<div class="Form-Tags P">';
         echo $Sender->Form->Label('Tags', 'Tags');
         echo $Sender->Form->TextBox('Tags', array('maxlength' => 255));
         echo '</div>';
      }
   }
   
   /**
    * Add javascript to the post/edit discussion page so that tagging autocomplete works.
    */
   public function PostController_Render_Before($Sender) {
      $Sender->AddCSSFile('token-input.css', 'plugins/Tagging');
      $Sender->AddJsFile('jquery.tokeninput.vanilla.js', 'plugins/Tagging');
      $Sender->AddJsFile('tagging.js', 'plugins/Tagging');
      $Sender->AddDefinition('PluginsTaggingAdd', Gdn::Session()->CheckPermission('Plugins.Tagging.Add'));
      $Sender->AddDefinition('PluginsTaggingSearchUrl', Gdn::Request()->Url('plugin/tagsearch'));
   }

   /**
    * Tag management (let admins rename tags, remove tags, etc).
    * 
    * TODO: manage the Plugins.Tagging.Required boolean setting that makes tagging required or not.
    * 
    * @param SettingsController $Sender
    */
   public function SettingsController_Tagging_Create($Sender, $Args) {
      $Sender->Permission('Garden.Settings.Manage');
      return $this->Dispatch($Sender);
   }
   
   /**
    * List all tags and allow searching
    * 
    * @param Gdn_Controller $Sender
    */
   public function Controller_Index($Sender) {
      $Sender->Title('Tagging');
      $Sender->AddSideMenu('settings/tagging');
      $Sender->AddCSSFile('plugins/Tagging/design/tagadmin.css');
      $Sender->AddJSFile('plugins/Tagging/js/admin.js');
      $SQL = Gdn::SQL();
      
      $Sender->Form->Method = 'get';
      $Sender->Form->InputPrefix = '';
      //$Sender->Form->Action = '/settings/tagging';

      list($Offset, $Limit) = OffsetLimit($Sender->Request->Get('Page'), 100);
      $Sender->SetData('_Limit', $Limit);
      
      if ($Search = $Sender->Request->Get('Search')) {
         $SQL->Like('Name', $Search , 'right');
      }
      
      $Data = $SQL
         ->Select('t.*')
         ->From('Tag t')
         ->OrderBy('t.Name', 'asc')
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
         $Sender->JsonTarget("#Tag-{$Tag['TagID']}", NULL, 'Remove');
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