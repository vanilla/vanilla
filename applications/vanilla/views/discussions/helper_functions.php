<?php if (!defined('APPLICATION')) exit();

function WriteDiscussion($Discussion, &$Sender, &$Session, $Alt) {
   $CssClass = 'Item';
   $CssClass .= $Discussion->Bookmarked == '1' ? ' Bookmarked' : '';
   $CssClass .= $Alt.' ';
   $CssClass .= $Discussion->Announce == '1' ? ' Announcement' : '';
   $CssClass .= $Discussion->Dismissed == '1' ? ' Dismissed' : '';
   $CssClass .= $Discussion->InsertUserID == $Session->UserID ? ' Mine' : '';
   $CssClass .= ($Discussion->CountUnreadComments > 0 && $Session->IsValid()) ? ' New' : '';
   $Sender->EventArguments['Discussion'] = &$Discussion;
   $First = UserBuilder($Discussion, 'First');
   $Last = UserBuilder($Discussion, 'Last');
   
   $Sender->FireEvent('BeforeDiscussionName');
   
   $DiscussionName = Gdn_Format::Text($Discussion->Name);
   if ($DiscussionName == '')
      $DiscussionName = T('Blank Discussion Topic');

   static $FirstDiscussion = TRUE;
   if (!$FirstDiscussion)
      $Sender->FireEvent('BetweenDiscussion');
   else
      $FirstDiscussion = FALSE;
?>
<li class="<?php echo $CssClass; ?>">
   <?php
   $Sender->FireEvent('BeforeDiscussionContent');
   WriteOptions($Discussion, $Sender, $Session);
   ?>
   <div class="ItemContent Discussion">
      <?php echo Anchor($DiscussionName, '/discussion/'.$Discussion->DiscussionID.'/'.Gdn_Format::Url($Discussion->Name).($Discussion->CountCommentWatch > 0 && C('Vanilla.Comments.AutoOffset') ? '/#Item_'.$Discussion->CountCommentWatch : ''), 'Title'); ?>
      <?php $Sender->FireEvent('AfterDiscussionTitle'); ?>
      <div class="Meta">
         <?php if ($Discussion->Announce == '1') { ?>
         <span class="Announcement"><?php echo T('Announcement'); ?></span>
         <?php } ?>
         <?php if ($Discussion->Closed == '1') { ?>
         <span class="Closed"><?php echo T('Closed'); ?></span>
         <?php } ?>
         <span class="CommentCount"><?php printf(Plural($Discussion->CountComments, '%s comment', '%s comments'), $Discussion->CountComments); ?></span>
         <?php
            if ($Session->IsValid() && $Discussion->CountUnreadComments > 0)
               echo '<strong>'.Plural($Discussion->CountUnreadComments, '%s New', '%s New Plural').'</strong>';

            if ($Discussion->LastCommentID != '') {
               echo '<span class="LastCommentBy">'.sprintf(T('Most recent by %1$s'), UserAnchor($Last)).'</span>';
               echo '<span class="LastCommentDate">'.Gdn_Format::Date($Discussion->LastDate).'</span>';
            } else {
               echo '<span class="LastCommentBy">'.sprintf(T('Started by %1$s'), UserAnchor($First)).'</span>';
               echo '<span class="LastCommentDate">'.Gdn_Format::Date($Discussion->FirstDate).'</span>';
            }
         
            if (C('Vanilla.Categories.Use'))
               echo Wrap(Anchor($Discussion->Category, '/categories/'.$Discussion->CategoryUrlCode, 'Category'));
               
            $Sender->FireEvent('DiscussionMeta');
         ?>
      </div>
   </div>
</li>
<?php
}

function WriteFilterTabs(&$Sender) {
   $Session = Gdn::Session();
   $Title = property_exists($Sender, 'Category') && is_object($Sender->Category) ? $Sender->Category->Name : T('All Discussions');
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
   if (is_numeric($CountBookmarks) && $CountBookmarks > 0)
      $Bookmarked .= '<span>'.$CountBookmarks.'</span>';

   if (is_numeric($CountDiscussions) && $CountDiscussions > 0)
      $MyDiscussions .= '<span>'.$CountDiscussions.'</span>';

   if (is_numeric($CountDrafts) && $CountDrafts > 0)
      $MyDrafts .= '<span>'.$CountDrafts.'</span>';
      
   ?>
<div class="Tabs DiscussionsTabs">
   <ul>
      <?php $Sender->FireEvent('BeforeDiscussionTabs'); ?>
      <li<?php echo strtolower($Sender->ControllerName) == 'discussionscontroller' && strtolower($Sender->RequestMethod) == 'index' ? ' class="Active"' : ''; ?>><?php echo Anchor(T('All Discussions'), 'discussions'); ?></li>
      <?php $Sender->FireEvent('AfterAllDiscussionsTab'); ?>
      <?php if ($CountBookmarks > 0 || $Sender->RequestMethod == 'bookmarked') { ?>
      <li<?php echo $Sender->RequestMethod == 'bookmarked' ? ' class="Active"' : ''; ?>><?php echo Anchor($Bookmarked, '/discussions/bookmarked', 'MyBookmarks'); ?></li>
      <?php
         $Sender->FireEvent('AfterBookmarksTab');
      }
      if ($CountDiscussions > 0 || $Sender->RequestMethod == 'mine') {
      ?>
      <li<?php echo $Sender->RequestMethod == 'mine' ? ' class="Active"' : ''; ?>><?php echo Anchor($MyDiscussions, '/discussions/mine', 'MyDiscussions'); ?></li>
      <?php
      }
      if ($CountDrafts > 0 || $Sender->ControllerName == 'draftscontroller') {
      ?>
      <li<?php echo $Sender->ControllerName == 'draftscontroller' ? ' class="Active"' : ''; ?>><?php echo Anchor($MyDrafts, '/drafts', 'MyDrafts'); ?></li>
      <?php
      }
      $Sender->FireEvent('AfterDiscussionTabs');
      ?>
   </ul>
   <?php
   if (property_exists($Sender, 'Category') && is_object($Sender->Category)) {
      ?>
      <div class="SubTab">↳ <?php echo $Sender->Category->Name; ?></div>
      <?php
   }
   ?>
</div>
   <?php
}

/**
 * Render options that the user has for this discussion.
 */
function WriteOptions($Discussion, &$Sender, &$Session) {
   if ($Session->IsValid() && $Sender->ShowOptions) {
      echo '<div class="Options">';
      // Bookmark link
      $Title = T($Discussion->Bookmarked == '1' ? 'Unbookmark' : 'Bookmark');
      echo Anchor(
         '<span class="Star">'
            .Img('applications/dashboard/design/images/pixel.png', array('alt' => $Title))
         .'</span>',
         '/vanilla/discussion/bookmark/'.$Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl),
         'Bookmark' . ($Discussion->Bookmarked == '1' ? ' Bookmarked' : ''),
         array('title' => $Title)
      );
      
      $Sender->Options = '';
      
      // Dismiss an announcement
      if (C('Vanilla.Discussions.Dismiss', 1) && $Discussion->Announce == '1' && $Discussion->Dismissed != '1')
         $Sender->Options .= '<li>'.Anchor(T('Dismiss'), 'vanilla/discussion/dismissannouncement/'.$Discussion->DiscussionID.'/'.$Session->TransientKey(), 'DismissAnnouncement') . '</li>';
      
      // Edit discussion
      if ($Discussion->FirstUserID == $Session->UserID || $Session->CheckPermission('Vanilla.Discussions.Edit', TRUE, 'Category', $Discussion->CategoryID))
         $Sender->Options .= '<li>'.Anchor(T('Edit'), 'vanilla/post/editdiscussion/'.$Discussion->DiscussionID, 'EditDiscussion') . '</li>';

      // Announce discussion
      if ($Session->CheckPermission('Vanilla.Discussions.Announce', TRUE, 'Category', $Discussion->CategoryID))
         $Sender->Options .= '<li>'.Anchor(T($Discussion->Announce == '1' ? 'Unannounce' : 'Announce'), 'vanilla/discussion/announce/'.$Discussion->DiscussionID.'/'.$Session->TransientKey(), 'AnnounceDiscussion') . '</li>';

      // Sink discussion
      if ($Session->CheckPermission('Vanilla.Discussions.Sink', TRUE, 'Category', $Discussion->CategoryID))
         $Sender->Options .= '<li>'.Anchor(T($Discussion->Sink == '1' ? 'Unsink' : 'Sink'), 'vanilla/discussion/sink/'.$Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl), 'SinkDiscussion') . '</li>';

      // Close discussion
      if ($Session->CheckPermission('Vanilla.Discussions.Close', TRUE, 'Category', $Discussion->CategoryID))
         $Sender->Options .= '<li>'.Anchor(T($Discussion->Closed == '1' ? 'Reopen' : 'Close'), 'vanilla/discussion/close/'.$Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl), 'CloseDiscussion') . '</li>';
      
      // Delete discussion
      if ($Session->CheckPermission('Vanilla.Discussions.Delete', TRUE, 'Category', $Discussion->CategoryID))
         $Sender->Options .= '<li>'.Anchor(T('Delete'), 'vanilla/discussion/delete/'.$Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl), 'DeleteDiscussion') . '</li>';
      
      // Allow plugins to add options
      $Sender->FireEvent('DiscussionOptions');
      
      if ($Sender->Options != '') {
      ?>
         <ul class="Options">
            <li>
               <strong><?php echo T('Options'); ?></strong>
               <ul>
                  <?php echo $Sender->Options; ?>
               </ul>
            </li>
         </ul>
      <?php
      }
      echo '</div>';
   }
}