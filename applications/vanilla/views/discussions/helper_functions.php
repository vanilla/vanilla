<?php if (!defined('APPLICATION')) exit();

if (!function_exists('AdminCheck')) {
function AdminCheck($Discussion = NULL, $Wrap = FALSE) {
   static $UseAdminChecks = NULL;
   if ($UseAdminChecks === NULL)
      $UseAdminChecks = C('Vanilla.AdminCheckboxes.Use') && Gdn::Session()->CheckPermission('Garden.Moderation.Manage');

   if (!$UseAdminChecks)
      return '';

   static $CanEdits = array(), $Checked = NULL;
   $Result = '';

   if ($Discussion) {
      if (!isset($CanEdits[$Discussion->CategoryID]))
         $CanEdits[$Discussion->CategoryID] = GetValue('PermsDiscussionsEdit', CategoryModel::Categories($Discussion->CategoryID));



      if ($CanEdits[$Discussion->CategoryID]) {   
         // Grab the list of currently checked discussions.
         if ($Checked === NULL) {
            $Checked = (array)Gdn::Session()->GetAttribute('CheckedDiscussions', array());

            if (!is_array($Checked))
               $Checked = array();
         }

         if (in_array($Discussion->DiscussionID, $Checked))
            $ItemSelected = ' checked="checked"';
         else
            $ItemSelected = '';

         $Result = <<<EOT
<span class="AdminCheck"><input type="checkbox" name="DiscussionID[]" value="{$Discussion->DiscussionID}" $ItemSelected /></span>
EOT;
      }
   } else {
      $Result = '<span class="AdminCheck"><input type="checkbox" name="Toggle" /></span>';
   }

   if ($Wrap) {
      $Result = $Wrap[0].$Result.$Wrap[1];
   }

   return $Result;
}
}

if (!function_exists('BookmarkButton')) {
   function BookmarkButton($Discussion) {
      if (!Gdn::Session()->IsValid())
         return '';
      
      // Bookmark link
      $Title = T($Discussion->Bookmarked == '1' ? 'Unbookmark' : 'Bookmark');
      return Anchor(
         $Title,
         '/vanilla/discussion/bookmark/'.$Discussion->DiscussionID.'/'.Gdn::Session()->TransientKey(),
         'Hijack Bookmark' . ($Discussion->Bookmarked == '1' ? ' Bookmarked' : ''),
         array('title' => $Title)
      );
   }
}

if (!function_exists('CategoryLink')):
   
function CategoryLink($Discussion, $Prefix = ' ') {
//   if (!$Force && Gdn::Controller()->Data('Category')) {
//      return;
//   }
   $Category = CategoryModel::Categories(GetValue('CategoryID', $Discussion));
   
   if ($Category) {
      return Wrap($Prefix.Anchor(htmlspecialchars($Category['Name']), $Category['Url']), 'span', array('class' => 'MItem Category'));
   }
}

endif;

if (!function_exists('DiscussionHeading')):
   
function DiscussionHeading() {
   return T('Discussion');
}

endif;

if (!function_exists('WriteDiscussion')):
function WriteDiscussion($Discussion, &$Sender, &$Session) {
   $CssClass = CssClass($Discussion);
   $DiscussionUrl = $Discussion->Url;
   $Category = CategoryModel::Categories($Discussion->CategoryID);
   
   if ($Session->UserID)
      $DiscussionUrl .= '#latest';
   
   $Sender->EventArguments['DiscussionUrl'] = &$DiscussionUrl;
   $Sender->EventArguments['Discussion'] = &$Discussion;
   $Sender->EventArguments['CssClass'] = &$CssClass;
   
   $First = UserBuilder($Discussion, 'First');
   $Last = UserBuilder($Discussion, 'Last');
   $Sender->EventArguments['FirstUser'] = &$First;
   $Sender->EventArguments['LastUser'] = &$Last;
   
   $Sender->FireEvent('BeforeDiscussionName');
   
   $DiscussionName = $Discussion->Name;
   if ($DiscussionName == '')
      $DiscussionName = T('Blank Discussion Topic');
      
   $Sender->EventArguments['DiscussionName'] = &$DiscussionName;

   static $FirstDiscussion = TRUE;
   if (!$FirstDiscussion)
      $Sender->FireEvent('BetweenDiscussion');
   else
      $FirstDiscussion = FALSE;
      
   $Discussion->CountPages = ceil($Discussion->CountComments / $Sender->CountCommentsPerPage);
?>
<li id="Discussion_<?php echo $Discussion->DiscussionID; ?>" class="<?php echo $CssClass; ?>">
   <?php
   if (!property_exists($Sender, 'CanEditDiscussions'))
      $Sender->CanEditDiscussions = GetValue('PermsDiscussionsEdit', CategoryModel::Categories($Discussion->CategoryID)) && C('Vanilla.AdminCheckboxes.Use');

   $Sender->FireEvent('BeforeDiscussionContent');

//   WriteOptions($Discussion, $Sender, $Session);
   ?>
   <span class="Options">
      <?php
      echo OptionsList($Discussion);
      echo BookmarkButton($Discussion);
      ?>
   </span>
   <div class="ItemContent Discussion">
      <div class="Title">
      <?php 
         echo AdminCheck($Discussion, array('', ' ')).
            Anchor($DiscussionName, $DiscussionUrl);
         $Sender->FireEvent('AfterDiscussionTitle'); 
      ?>
      </div>
      <div class="Meta Meta-Discussion">
         <?php 
         WriteTags($Discussion);
         ?>
         <span class="MItem MCount ViewCount"><?php
            printf(PluralTranslate($Discussion->CountViews, 
               '%s view html', '%s views html', '%s view', '%s views'),
               BigPlural($Discussion->CountViews, '%s view'));
         ?></span>
         <span class="MItem MCount CommentCount"><?php
            printf(PluralTranslate($Discussion->CountComments, 
               '%s comment html', '%s comments html', '%s comment', '%s comments'),
               BigPlural($Discussion->CountComments, '%s comment'));
         ?></span>
         <span class="MItem MCount DiscussionScore Hidden"><?php
         $Score = $Discussion->Score;
         if ($Score == '') $Score = 0;
         printf(Plural($Score, 
            '%s point', '%s points',
            BigPlural($Score, '%s point')));
         ?></span>
         <?php
            echo NewComments($Discussion);
         
            $Sender->FireEvent('AfterCountMeta');

            if ($Discussion->LastCommentID != '') {
               echo ' <span class="MItem LastCommentBy">'.sprintf(T('Most recent by %1$s'), UserAnchor($Last)).'</span> ';
               echo ' <span class="MItem LastCommentDate">'.Gdn_Format::Date($Discussion->LastDate, 'html').'</span>';
            } else {
               echo ' <span class="MItem LastCommentBy">'.sprintf(T('Started by %1$s'), UserAnchor($First)).'</span> ';
               echo ' <span class="MItem LastCommentDate">'.Gdn_Format::Date($Discussion->FirstDate, 'html');
               
               if ($Source = GetValue('Source', $Discussion)) {
                  echo ' '.sprintf(T('via %s'), T($Source.' Source', $Source));
               }
               
               echo '</span> ';
            }
         
            if (C('Vanilla.Categories.Use') && $Category)
               echo Wrap(Anchor(htmlspecialchars($Discussion->Category), CategoryUrl($Discussion->CategoryUrlCode)), 'span', array('class' => 'MItem Category '.$Category['CssClass']));
               
            $Sender->FireEvent('DiscussionMeta');
         ?>
      </div>
   </div>
   <?php $Sender->FireEvent('AfterDiscussionContent'); ?>
</li>
<?php
}
endif;

if (!function_exists('WriteMiniPager')):
function WriteMiniPager($Discussion) {
   if (!property_exists($Discussion, 'CountPages'))
      return;
   
   if ($Discussion->CountPages > 1) {
      echo '<span class="MiniPager">';
         if ($Discussion->CountPages < 5) {
            for ($i = 0; $i < $Discussion->CountPages; $i++) {
               WritePageLink($Discussion, $i+1);
            }
         } else {
            WritePageLink($Discussion, 1);
            WritePageLink($Discussion, 2);
            echo '<span class="Elipsis">...</span>';
            WritePageLink($Discussion, $Discussion->CountPages-1);
            WritePageLink($Discussion, $Discussion->CountPages);
            // echo Anchor('Go To Page', '#', 'GoToPageLink');
         }
      echo '</span>';
   }
}
endif;

if (!function_exists('WritePageLink')):
function WritePageLink($Discussion, $PageNumber) {
   echo Anchor($PageNumber, DiscussionUrl($Discussion, $PageNumber));
}
endif;

if (!function_exists('NewComments')):
function NewComments($Discussion) {
   if (!Gdn::Session()->IsValid())
      return '';
   
   if ($Discussion->CountUnreadComments === TRUE) {
      $Title = htmlspecialchars(T("You haven't read this yet."));
      
      return ' <strong class="HasNew JustNew NewCommentCount" title="'.$Title.'">'.T('new discussion', 'new').'</strong>';
   } elseif ($Discussion->CountUnreadComments > 0) {
      $Title = htmlspecialchars(Plural($Discussion->CountUnreadComments, "%s new comment since you last read this.", "%s new comments since you last read this."));
      
      return ' <strong class="HasNew NewCommentCount" title="'.$Title.'">'.Plural($Discussion->CountUnreadComments, '%s new', '%s new plural', BigPlural($Discussion->CountUnreadComments, '%s new', '%s new plural')).'</strong>';
   }
   return '';
}
endif;

if (!function_exists('Tag')):
function Tag($Discussion, $Column, $Code, $CssClass = FALSE) {
   $Discussion = (object)$Discussion;
   
   if (is_numeric($Discussion->$Column) && !$Discussion->$Column)
      return '';
   if (!is_numeric($Discussion->$Column) && strcasecmp($Discussion->$Column, $Code) != 0)
      return;

   if (!$CssClass)
      $CssClass = "Tag-$Code";

   return ' <span class="Tag '.$CssClass.'" title="'.htmlspecialchars(T($Code)).'">'.T($Code).'</span> ';
}
endif;

if (!function_exists('WriteTags')):
function WriteTags($Discussion) {
   Gdn::Controller()->FireEvent('BeforeDiscussionMeta');

   echo Tag($Discussion, 'Announce', 'Announcement');
   echo Tag($Discussion, 'Closed', 'Closed');

   Gdn::Controller()->FireEvent('AfterDiscussionLabels');
}
endif;

if (!function_exists('WriteFilterTabs')):
function WriteFilterTabs($Sender) {
   $Session = Gdn::Session();
   $Title = property_exists($Sender, 'Category') ? GetValue('Name', $Sender->Category, '') : '';
   if ($Title == '')
      $Title = T('All Discussions');
      
   $Bookmarked = T('My Bookmarks');
   $MyDiscussions = T('My Discussions');
   $MyDrafts = T('My Drafts');
   $CountBookmarks = 0;
   $CountDiscussions = 0;
   $CountDrafts = 0;
   
   if ($Session->IsValid()) {
      $CountBookmarks = $Session->User->CountBookmarks;
      $CountDiscussions = $Session->User->CountDiscussions;
      $CountDrafts = $Session->User->CountDrafts;
   }
   
   if (C('Vanilla.Discussions.ShowCounts', TRUE)) {
      $Bookmarked .= CountString($CountBookmarks, Url('/discussions/UserBookmarkCount'));
      $MyDiscussions .= CountString($CountDiscussions);
      $MyDrafts .= CountString($CountDrafts);
   }
      
   ?>
<div class="Tabs DiscussionsTabs">
   <?php
   if (!property_exists($Sender, 'CanEditDiscussions'))
      $Sender->CanEditDiscussions = $Session->CheckPermission('Vanilla.Discussions.Edit', TRUE, 'Category', 'any') && C('Vanilla.AdminCheckboxes.Use');
   
   if ($Sender->CanEditDiscussions) {
   ?>
   <span class="Options"><span class="AdminCheck">
      <input type="checkbox" name="Toggle" />
   </span></span>
   <?php } ?>
   <ul>
      <?php $Sender->FireEvent('BeforeDiscussionTabs'); ?>
      <li<?php echo strtolower($Sender->ControllerName) == 'discussionscontroller' && strtolower($Sender->RequestMethod) == 'index' ? ' class="Active"' : ''; ?>><?php echo Anchor(T('All Discussions'), 'discussions', 'TabLink'); ?></li>
      <?php $Sender->FireEvent('AfterAllDiscussionsTab'); ?>

      <?php
      if (C('Vanilla.Categories.ShowTabs')) {
         $CssClass = '';
         if (strtolower($Sender->ControllerName) == 'categoriescontroller' && strtolower($Sender->RequestMethod) == 'all') {
            $CssClass = 'Active';
         }

         echo " <li class=\"$CssClass\">".Anchor(T('Categories'), '/categories/all', 'TabLink').'</li> ';
      }
      ?>
      <?php if ($CountBookmarks > 0 || $Sender->RequestMethod == 'bookmarked') { ?>
      <li<?php echo $Sender->RequestMethod == 'bookmarked' ? ' class="Active"' : ''; ?>><?php echo Anchor($Bookmarked, '/discussions/bookmarked', 'MyBookmarks TabLink'); ?></li>
      <?php
         $Sender->FireEvent('AfterBookmarksTab');
      }
      if (($CountDiscussions > 0 || $Sender->RequestMethod == 'mine') && C('Vanilla.Discussions.ShowMineTab', TRUE)) {
      ?>
      <li<?php echo $Sender->RequestMethod == 'mine' ? ' class="Active"' : ''; ?>><?php echo Anchor($MyDiscussions, '/discussions/mine', 'MyDiscussions TabLink'); ?></li>
      <?php
      }
      if ($CountDrafts > 0 || $Sender->ControllerName == 'draftscontroller') {
      ?>
      <li<?php echo $Sender->ControllerName == 'draftscontroller' ? ' class="Active"' : ''; ?>><?php echo Anchor($MyDrafts, '/drafts', 'MyDrafts TabLink'); ?></li>
      <?php
      }
      $Sender->FireEvent('AfterDiscussionTabs');
      ?>
   </ul>
</div>
   <?php
}
endif;

if (!function_exists('OptionsList')):
function OptionsList($Discussion) {
   $Sender = Gdn::Controller();
   $Session = Gdn::Session();
   
   if ($Session->IsValid() && $Sender->ShowOptions) {
      $Sender->Options = '';
      
      // Dismiss an announcement
      if (C('Vanilla.Discussions.Dismiss', 1) && $Discussion->Announce == '1' && $Discussion->Dismissed != '1')
         $Sender->Options .= '<li>'.Anchor(T('Dismiss'), "vanilla/discussion/dismissannouncement?discussionid={$Discussion->DiscussionID}", 'DismissAnnouncement Hijack') . '</li>';
      
      // Edit discussion
      if ($Discussion->FirstUserID == $Session->UserID || $Session->CheckPermission('Vanilla.Discussions.Edit', TRUE, 'Category', $Discussion->PermissionCategoryID))
         $Sender->Options .= '<li>'.Anchor(T('Edit'), 'vanilla/post/editdiscussion/'.$Discussion->DiscussionID, 'EditDiscussion') . '</li>';

      // Announce discussion
      if ($Session->CheckPermission('Vanilla.Discussions.Announce', TRUE, 'Category', $Discussion->PermissionCategoryID))
         $Sender->Options .= '<li>'.Anchor(T('Announce...'), '/discussion/announce?discussionid='.$Discussion->DiscussionID.'&Target='.urlencode($Sender->SelfUrl), 'Popup AnnounceDiscussion') . '</li>';

      // Sink discussion
      if ($Session->CheckPermission('Vanilla.Discussions.Sink', TRUE, 'Category', $Discussion->PermissionCategoryID)) {
         $NewSink = (int)!$Discussion->Sink;
         $Sender->Options .= '<li>'.Anchor(T($Discussion->Sink == '1' ? 'Unsink' : 'Sink'), "vanilla/discussion/sink?discussionid={$Discussion->DiscussionID}&sink={$NewSink}", 'SinkDiscussion Hijack') . '</li>';
      }

      // Close discussion
      if ($Session->CheckPermission('Vanilla.Discussions.Close', TRUE, 'Category', $Discussion->PermissionCategoryID)) {
         $NewClosed = (int)!$Discussion->Closed;
         $Sender->Options .= '<li>'.Anchor(T($Discussion->Closed == '1' ? 'Reopen' : 'Close'), "/discussion/close?discussionid={$Discussion->DiscussionID}&close=$NewClosed", 'CloseDiscussion Hijack') . '</li>';
      }
      
      // Delete discussion
      if ($Session->CheckPermission('Vanilla.Discussions.Delete', TRUE, 'Category', $Discussion->PermissionCategoryID))
         $Sender->Options .= '<li>'.Anchor(T('Delete'), '/discussion/delete?discussionid='.$Discussion->DiscussionID, 'DeleteDiscussion Popup') . '</li>';
      
      // Allow plugins to add options.
      $Sender->EventArguments['Discussion'] = $Discussion;
      $Sender->FireEvent('DiscussionOptions');
      
      if ($Sender->Options != '') {
         $Result = '<span class="ToggleFlyout OptionsMenu">'.
            '<span class="OptionsTitle" title="'.T('Options').'">'.T('Options').'</span>'.
            '<span class="SpFlyoutHandle"></span>'.
            '<ul class="Flyout MenuItems">'.
               $Sender->Options.
            '</ul>'.
            '</span>';
         
         return $Result;
      }
     
   }
   return '';
}

endif;


if (!function_exists('WriteOptions')):
/**
 * Render options that the user has for this discussion.
 */
function WriteOptions($Discussion) {
   if (!Gdn::Session()->IsValid() || !Gdn::Controller()->ShowOptions)
      return;
   
   
   echo '<span class="Options">';
   
   // Options list.
   echo OptionsList($Discussion);

   // Bookmark button.
   echo BookmarkButton($Discussion);

   // Admin check.
   echo AdminCheck($Discussion);

   echo '</span>';
}
endif;