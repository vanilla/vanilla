<?php if (!defined('APPLICATION')) exit();

function WriteDiscussion($Discussion, &$Sender, &$Session, $Alt2) {
   $CssClass = CssClass($Discussion);
   $DiscussionUrl = $Discussion->Url;
   
   if ($Session->UserID)
      $DiscussionUrl .= '#Item_'.($Discussion->CountCommentWatch);
   
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
<li class="<?php echo $CssClass; ?>">
   <?php
   if (!property_exists($Sender, 'CanEditDiscussions'))
      $Sender->CanEditDiscussions = GetValue('PermsDiscussionsEdit', CategoryModel::Categories($Discussion->CategoryID)) && C('Vanilla.AdminCheckboxes.Use');

   $Sender->FireEvent('BeforeDiscussionContent');

   WriteOptions($Discussion, $Sender, $Session);
   ?>
   <div class="ItemContent Discussion">
      <?php echo Anchor($DiscussionName, $DiscussionUrl, 'Title'); ?>
      <?php $Sender->FireEvent('AfterDiscussionTitle'); ?>
      <div class="Meta">
         <?php 
         WriteTags($Discussion);
         ?>
         <span class="MItem CommentCount"><?php 
            printf(Plural($Discussion->CountComments, '%s comment', '%s comments'), $Discussion->CountComments);
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
         
            if (C('Vanilla.Categories.Use') && $Discussion->CategoryUrlCode != '')
               echo Wrap(Anchor($Discussion->Category, '/categories/'.rawurlencode($Discussion->CategoryUrlCode)), 'span', array('class' => 'MItem Category'));
               
            $Sender->FireEvent('DiscussionMeta');
         ?>
      </div>
   </div>
</li>
<?php
}

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
function WritePageLink($Discussion, $PageNumber) {
   echo Anchor($PageNumber, '/discussion/'.$Discussion->DiscussionID.'/'.Gdn_Format::Url($Discussion->Name).'/p'.$PageNumber);
}


function CssClass($Discussion) {
   static $Alt = FALSE;
   $CssClass = 'Item';
   $CssClass .= $Discussion->Bookmarked == '1' ? ' Bookmarked' : '';
   $CssClass .= $Alt ? ' Alt ' : '';
   $Alt = !$Alt;
   $CssClass .= $Discussion->Announce == '1' ? ' Announcement' : '';
   $CssClass .= $Discussion->Dismissed == '1' ? ' Dismissed' : '';
   $CssClass .= $Discussion->InsertUserID == Gdn::Session()->UserID ? ' Mine' : '';
   $CssClass .= ($Discussion->CountUnreadComments > 0 && Gdn::Session()->IsValid()) ? ' New' : '';
   
   return $CssClass;
}

function NewComments($Discussion) {
   if (!Gdn::Session()->IsValid())
      return '';
   
   if ($Discussion->CountUnreadComments === TRUE)
      return ' <strong class="HasNew">'.T('new discussion', 'new').'</strong>';
   elseif ($Discussion->CountUnreadComments > 0)
      return ' <strong class="HasNew">'.Plural($Discussion->CountUnreadComments, '%s new', '%s new plural').'</strong>';
   return '';
}

function Tag($Discussion, $Column, $Code, $CssClass = FALSE) {
   if (!$Discussion->$Column)
      return '';
   
   if (!$CssClass)
      $CssClass = "Tag $Code";
   
   return ' <span class="Tag '.$CssClass.'">'.T($Code).'</span> ';
}

function WriteTags($Discussion) {
   Gdn::Controller()->FireEvent('BeforeDiscussionMeta');
         
   echo Tag($Discussion, 'Announce', 'Announcement');
   echo Tag($Discussion, 'Closed', 'Closed');
   
   Gdn::Controller()->FireEvent('AfterDiscussionLabels');
}

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

/**
 * Render options that the user has for this discussion.
 */
function WriteOptions($Discussion, &$Sender, &$Session) {
   if ($Session->IsValid() && $Sender->ShowOptions) {
      echo '<span class="Options">';
      $Sender->Options = '';
      
      // Dismiss an announcement
      if (C('Vanilla.Discussions.Dismiss', 1) && $Discussion->Announce == '1' && $Discussion->Dismissed != '1')
         $Sender->Options .= '<li>'.Anchor(T('Dismiss'), 'vanilla/discussion/dismissannouncement/'.$Discussion->DiscussionID.'/'.$Session->TransientKey(), 'DismissAnnouncement') . '</li>';
      
      // Edit discussion
      if ($Discussion->FirstUserID == $Session->UserID || $Session->CheckPermission('Vanilla.Discussions.Edit', TRUE, 'Category', $Discussion->PermissionCategoryID))
         $Sender->Options .= '<li>'.Anchor(T('Edit'), 'vanilla/post/editdiscussion/'.$Discussion->DiscussionID, 'EditDiscussion') . '</li>';

      // Announce discussion
      if ($Session->CheckPermission('Vanilla.Discussions.Announce', TRUE, 'Category', $Discussion->PermissionCategoryID))
         $Sender->Options .= '<li>'.Anchor(T($Discussion->Announce == '1' ? 'Unannounce' : 'Announce'), 'vanilla/discussion/announce/'.$Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl), 'AnnounceDiscussion') . '</li>';

      // Sink discussion
      if ($Session->CheckPermission('Vanilla.Discussions.Sink', TRUE, 'Category', $Discussion->PermissionCategoryID))
         $Sender->Options .= '<li>'.Anchor(T($Discussion->Sink == '1' ? 'Unsink' : 'Sink'), 'vanilla/discussion/sink/'.$Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl), 'SinkDiscussion') . '</li>';

      // Close discussion
      if ($Session->CheckPermission('Vanilla.Discussions.Close', TRUE, 'Category', $Discussion->PermissionCategoryID))
         $Sender->Options .= '<li>'.Anchor(T($Discussion->Closed == '1' ? 'Reopen' : 'Close'), 'vanilla/discussion/close/'.$Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl), 'CloseDiscussion') . '</li>';
      
      // Delete discussion
      if ($Session->CheckPermission('Vanilla.Discussions.Delete', TRUE, 'Category', $Discussion->PermissionCategoryID))
         $Sender->Options .= '<li>'.Anchor(T('Delete'), 'vanilla/discussion/delete/'.$Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl), 'DeleteDiscussion') . '</li>';
      
      // Allow plugins to add options
      $Sender->FireEvent('DiscussionOptions');
      
      // Bookmark link
      $Title = T($Discussion->Bookmarked == '1' ? 'Unbookmark' : 'Bookmark');
      echo Anchor(
         $Title,
         '/vanilla/discussion/bookmark/'.$Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl),
         'Bookmark' . ($Discussion->Bookmarked == '1' ? ' Bookmarked' : ''),
         array('title' => $Title)
      );
      
      if ($Sender->Options != '') {
         echo '<span class="ToggleFlyout OptionsMenu">';
            echo '<span class="OptionsTitle" title="'.T('Options').'">'.T('Options').'</span>';
            echo '<ul class="Flyout MenuItems">';
               echo $Sender->Options;
            echo '</ul>';
         echo '</span>';
      }

      // Admin check.
      if ($Sender->CanEditDiscussions) {
         if (!property_exists($Sender, 'CheckedDiscussions')) {
            $Sender->CheckedDiscussions = (array)$Session->GetAttribute('CheckedDiscussions', array());
            if (!is_array($Sender->CheckedDiscussions))
               $Sender->CheckedDiscussions = array();
         }

         $ItemSelected = in_array($Discussion->DiscussionID, $Sender->CheckedDiscussions);
         echo '<span class="AdminCheck"><input type="checkbox" name="DiscussionID[]" value="'.$Discussion->DiscussionID.'"'.($ItemSelected?' checked="checked"':'').' /></span>';
      }
      
      echo '</span>';
   }
}

function WriteCheckController() {
   $CanEditDiscussions = Gdn::Session()->CheckPermission('Vanilla.Discussions.Edit', TRUE, 'Category', 'any') && C('Vanilla.AdminCheckboxes.Use');
   if ($CanEditDiscussions) {
   ?>
   <span class="Options ControlOptions"><span class="AdminCheck">
      <input type="checkbox" name="Toggle" />
   </span></span>
   <?php
   }
}