<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

// Define the plugin:
$PluginInfo['Tagging'] = array(
   'Name' => 'Tagging',
   'Description' => 'Allow tagging of discussions.',
   'Version' => '1.3',
   'SettingsUrl' => '/dashboard/settings/tagging',
   'SettingsPermission' => 'Garden.Settings.Manage',
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://markosullivan.ca'
);

/*
v1.2 (2011-10-02 Matt Lincoln Russell lincoln@vanillaforums.com)
- Added inline tags after first comment in discussion view.

v1.3 (2011-10-21 Lincoln)
- Removed redundant enable/disable plugin functionality.

*/

class TaggingPlugin extends Gdn_Plugin {
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
      $this->_AddTagModule($Sender);
   }

   /**
    * Display the tag module in a discussion.
    */
   public function DiscussionController_Render_Before($Sender) {
      $this->_AddTagModule($Sender);
   }

   /**
    * Show tags after first comment.
    */
   public function DiscussionController_AfterCommentBody_Handler($Sender) {
      // Allow disabling of inline tags.
      if (C('Plugins.Tagging.DisableInline', FALSE))
         return;

      if (!property_exists($Sender->EventArguments['Object'], 'CommentID')) {
         $DiscussionID = property_exists($Sender, 'DiscussionID') ? $Sender->DiscussionID : 0;
         include_once(PATH_PLUGINS.'/Tagging/class.tagmodule.php');
         $TagModule = new TagModule($Sender);
         $TagModule->GetData($DiscussionID);
         echo $TagModule->InlineDisplay();
      }
   }

   /**
    * Display the tag module on discussions lists.
    * @param DiscussionsController $Sender
    */
   public function DiscussionsController_Render_Before($Sender) {
      $this->_AddTagModule($Sender);
   }

   /**
    * Load discussions for a specific tag.
    */
   public function DiscussionsController_Tagged_Create($Sender) {
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
      list($Offset, $Limit) = OffsetLimit($Page, Gdn::Config('Vanilla.Discussions.PerPage', 30));

      $Sender->SetData('Tag', $Tag, TRUE);
      $Sender->Title(T('Tagged with ').htmlspecialchars($Tag));
      $Sender->Head->Title($Sender->Head->Title());
      if (urlencode($Tag) == $Tag) {
         $Sender->CanonicalUrl(Url(ConcatSep('/', 'discussions/tagged/'.urlencode($Tag), PageNumber($Offset, $Limit, TRUE)), TRUE));
      } else {
         $Sender->CanonicalUrl(Url(ConcatSep('/', 'discussions/tagged', PageNumber($Offset, $Limit, TRUE)).'?Tag='.urlencode($Tag), TRUE));
      }

      if ($Sender->Head) {
         $Sender->AddJsFile('discussions.js');
         $Sender->AddJsFile('bookmark.js');
			$Sender->AddJsFile('js/library/jquery.menu.js');
         $Sender->AddJsFile('options.js');
         $Sender->Head->AddRss($Sender->SelfUrl.'/feed.rss', $Sender->Head->Title());
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
      $Sender->DiscussionData = $DiscussionModel->Get(FALSE);

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

      // Set a definition of the user's current timezone from the db. jQuery
      // will pick this up, compare to the browser, and update the user's
      // timezone if necessary.
      $CurrentUser = Gdn::Session()->User;
      if (is_object($CurrentUser)) {
         $ClientHour = $CurrentUser->HourOffset + date('G', time());
         $Sender->AddDefinition('SetClientHour', $ClientHour);
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
      $IsInsert = GetValue('Insert', $Sender->EventArguments);
      $FormTags = trim(strtolower(GetValue('Tags', $FormPostValues, '')));
      $FormTags = TagModel::SplitTags($FormTags);
      // Find out which of these tags is not yet in the tag table
      $ExistingTagData = $Sender->SQL->Select('TagID, Name')->From('Tag')->WhereIn('Name', $FormTags)->Get();
      $NewTags = $FormTags;
      $Tags = array(); // <-- Build a complete associative array of $Tags[TagID] => TagName values for this discussion.
      foreach ($ExistingTagData as $ExistingTag) {
         if (in_array($ExistingTag->Name, $NewTags))
            unset($NewTags[array_search($ExistingTag->Name, $NewTags)]);

         $Tags[$ExistingTag->TagID] = $ExistingTag->Name;
      }

      // Insert the missing ones
      foreach ($NewTags as $NewTag) {
         $TagID = $Sender->SQL->Insert(
               'Tag',
               array(
                  'Name' => strtolower($NewTag),
                  'InsertUserID' => Gdn::Session()->UserID,
                  'DateInserted' => Gdn_Format::ToDateTime(),
                  'CountDiscussions' => 0
               )
            );
         $Tags[$TagID] = $NewTag;
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
         $Sender->SQL->Insert('TagDiscussion', array('DiscussionID' => $DiscussionID, 'TagID' => $TagID));
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
   public function DiscussionModel_BeforeSaveDiscussion_Handler($Sender) {
      $FormPostValues = GetValue('FormPostValues', $Sender->EventArguments, array());
      $TagsString = trim(strtolower(GetValue('Tags', $FormPostValues, '')));
      $NumTagsMax = C('Plugin.Tagging.Max', 5);
      // Tags can only contain unicode and the following ASCII: a-z 0-9 + # _ .
      if (StringIsNullOrEmpty($TagsString) && C('Plugins.Tagging.Required')) {
         $Sender->Validation->AddValidationResult('Tags', 'You must specify at least one tag.');
      } else {
         $Tags = TagModel::SplitTags($TagsString);
         if (!TagModel::ValidateTags($Tags)) {
            $Sender->Validation->AddValidationResult('Tags', '@'.T('ValidateTag', 'Tags cannot contain spaces.'));
         } elseif (count($Tags) > $NumTagsMax) {
            $Sender->Validation->AddValidationResult('Tags', '@'.sprintf(T('You can only specify up to %s tags.'), $NumTagsMax));
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
      $Query = GetIncomingValue('q');
      $Data = array();
      $Database = Gdn::Database();
      if ($Query) {
         $Test = Gdn::SQL()->Limit(1)->Get('Tag')->FirstRow(DATASET_TYPE_ARRAY);
         if (isset($Test['Type'])) {
            Gdn::SQL()->Where('Type', ''); // other uis can set a different type
         }

         $TagData = Gdn::SQL()->Select('TagID, Name')->From('Tag')->Like('Name', $Query)->Limit(20)->Get();
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
   protected function _SetTagSql($Sql, $Tag, $Limit, $Offset = 0, $Op = 'or') {
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
   public function PostController_BeforeFormButtons_Handler($Sender) {
      if (in_array($Sender->RequestMethod, array('discussion', 'editdiscussion'))) {
         $Discussion = GetValue('Discussion', $Sender->EventArguments);
         if ($Discussion && !$Sender->Form->IsPostBack()) {
            // Load the existing tags.
            $Tags = Gdn::SQL()
               ->Select('t.*')
               ->From('TagDiscussion td')
               ->Join('Tag t', 'td.TagID = t.TagID')
               ->Where('td.DiscussionID', GetValue('DiscussionID', $Discussion))
               ->Where('t.Type', '')
               ->Get()->ResultArray();

            $Tags = ConsolidateArrayValuesByKey($Tags, 'Name');
            $Sender->Form->SetValue('Tags', implode(' ', $Tags));
         }

         echo $Sender->Form->Label('Tags', 'Tags');
         echo $Sender->Form->TextBox('Tags', array('maxlength' => 255));
      }
   }

   /**
    * Add javascript to the post/edit discussion page so that tagging autocomplete works.
    */
   public function PostController_Render_Before($Sender) {
      $Sender->AddCSSFile('plugins/Tagging/design/token-input.css');
      $Sender->AddJsFile('plugins/Tagging/jquery.tokeninput.js');
      $Sender->AddJsFile($this->GetResource('tagging.js', FALSE,FALSE));
      $Sender->Head->AddString('<script type="text/javascript">
   jQuery(document).ready(function($) {
      $("#Form_Tags").tokenInput("'.Gdn::Request()->Url('plugin/tagsearch').'", {
         hintText: "Start to type...",
         searchingText: "Searching...",
         searchDelay: 300,
         minChars: 1,
         maxLength: 25,
         onFocus: function() { $(".Help").hide(); $(".HelpTags").show(); }
     });
   });
</script>');
   }

   /**
    * Edit Tag form.
    */
   public function SettingsController_EditTag_Create($Sender) {
      $Sender->Permission('Garden.Settings.Manage');
      $Sender->Title(T('Edit Tag'));
      $Sender->AddSideMenu('settings/tagging');
      $TagID = GetValue(0, $Sender->RequestArgs);
      $TagModel = new TagModel;
      $Sender->Tag = $TagModel->GetWhere(array('TagID' => $TagID))->FirstRow();

      // Set the model on the form.
      $Sender->Form->SetModel($TagModel);

      // Make sure the form knows which item we are editing.
      $Sender->Form->AddHidden('TagID', $TagID);

      if (!$Sender->Form->AuthenticatedPostBack()) {
         $Sender->Form->SetData($Sender->Tag);
      } else {
         // Make sure the tag is valid
         $Tag = $Sender->Form->GetFormValue('Name');
         if (!TagModel::ValidateTag($Tag))
            $Sender->Form->AddError('@'.T('ValidateTag', 'Tags cannot contain spaces.'));

         // Make sure that the tag name is not already in use.
         if ($TagModel->GetWhere(array('TagID <>' => $TagID, 'Name' => $Tag))->NumRows() > 0) {
            $Sender->SetData('MergeTagVisible', TRUE);
            if (!$Sender->Form->GetFormValue('MergeTag')) {
               $Sender->Form->AddError('The specified tag name is already in use.');
            }
         }

         if ($Sender->Form->Save())
            $Sender->InformMessage(T('Your changes have been saved.'));
      }

      $Sender->Render('EditTag', '', 'plugins/Tagging');
   }

   /**
    * Delete a Tag
    */
   public function SettingsController_DeleteTag_Create($Sender) {
      $Sender->Permission('Garden.Settings.Manage');
      if (Gdn::Session()->ValidateTransientKey(GetValue(1, $Sender->RequestArgs))) {
         $TagID = GetValue(0, $Sender->RequestArgs);
         // Delete tag & tag relations.
         $SQL = Gdn::SQL();
         $SQL->Delete('TagDiscussion', array('TagID' => $TagID));
         $SQL->Delete('Tag', array('TagID' => $TagID));
      }
      $Sender->DeliveryType(DELIVERY_TYPE_BOOL);
      $Sender->Render();
   }


   /**
    * Tag management (let admins rename tags, remove tags, etc).
    * TODO: manage the Plugins.Tagging.Required boolean setting that makes tagging required or not.
    * @param SettingsController $Sender
    */
   public function SettingsController_Tagging_Create($Sender, $Args) {
      $Sender->Permission('Garden.Settings.Manage');
      $Sender->Title('Tagging');
      $Sender->AddSideMenu('settings/tagging');
      $Sender->AddCSSFile('plugins/Tagging/design/tagadmin.css');
      $Sender->AddJSFile('plugins/Tagging/admin.js');
      $SQL = Gdn::SQL();

      $Sender->Form->Method = 'get';
      $Sender->Form->InputPrefix = '';
      $Sender->Form->Action = '/settings/tagging';

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

      $Sender->Render('Tagging', '', 'plugins/Tagging');
   }

   /**
    * Setup is called when the plugin is enabled.
    */
   public function Setup() {
      // No setup required
   }

   /**
    * Adds the tag module to the page.
    */
   private function _AddTagModule($Sender) {
      $Sender->AddCSSFile('plugins/Tagging/design/tag.css');
      $DiscussionID = property_exists($Sender, 'DiscussionID') ? $Sender->DiscussionID : 0;
      include_once(PATH_PLUGINS.'/Tagging/class.tagmodule.php');
      $TagModule = new TagModule($Sender);
      $TagModule->GetData($DiscussionID);
      $Sender->AddModule($TagModule);
   }

}