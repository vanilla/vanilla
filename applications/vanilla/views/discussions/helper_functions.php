<?php if (!defined('APPLICATION')) exit();

function WriteDiscussion($Discussion, &$Sender, &$Session, $Alt) {
   $CssClass = 'DiscussionRow';
   $CssClass .= $Discussion->Bookmarked == '1' ? ' Bookmarked' : '';
   $CssClass .= $Alt.' ';
   $CssClass .= $Discussion->Announce == '1' ? ' Announcement' : '';
   $CssClass .= $Discussion->InsertUserID == $Session->UserID ? ' Mine' : '';
   $CountUnreadComments = $Discussion->CountComments - $Discussion->CountCommentWatch;
   $CssClass .= ($CountUnreadComments > 0 && $Session->IsValid()) ? ' New' : '';
   $Sender->EventArguments['Discussion'] = &$Discussion;
?>
<li class="<?php echo $CssClass; ?>">
   <ul class="Discussion">
      <?php
      if ($Sender->ShowOptions) {
      ?>
      <li class="Options">
         <?php
            // Build up the options that the user has for each discussion
            if ($Session->IsValid()) {
               // Bookmark link
               echo Anchor(
                  '<span>*</span>',
                  '/vanilla/discussion/bookmark/'.$Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl),
                  'Bookmark' . ($Discussion->Bookmarked == '1' ? ' Bookmarked' : ''),
                  array('title' => Gdn::Translate($Discussion->Bookmarked == '1' ? 'Unbookmark' : 'Bookmark'))
               );
               
               $Sender->Options = '';
               
               // Dismiss an announcement
               if ($Discussion->Announce == '1' && $Discussion->Dismissed != '1')
                  $Sender->Options .= '<li>'.Anchor('Dismiss', 'vanilla/discussion/dismissannouncement/'.$Discussion->DiscussionID.'/'.$Session->TransientKey(), 'DismissAnnouncement') . '</li>';
               
               // Edit discussion
               if ($Discussion->FirstUserID == $Session->UserID || $Session->CheckPermission('Vanilla.Discussions.Edit', $Discussion->CategoryID))
                  $Sender->Options .= '<li>'.Anchor('Edit', 'vanilla/post/editdiscussion/'.$Discussion->DiscussionID, 'EditDiscussion') . '</li>';
      
               // Announce discussion
               if ($Session->CheckPermission('Vanilla.Discussions.Announce', $Discussion->CategoryID))
                  $Sender->Options .= '<li>'.Anchor($Discussion->Announce == '1' ? 'Unannounce' : 'Announce', 'vanilla/discussion/announce/'.$Discussion->DiscussionID.'/'.$Session->TransientKey(), 'AnnounceDiscussion') . '</li>';
      
               // Sink discussion
               if ($Session->CheckPermission('Vanilla.Discussions.Sink', $Discussion->CategoryID))
                  $Sender->Options .= '<li>'.Anchor($Discussion->Sink == '1' ? 'Unsink' : 'Sink', 'vanilla/discussion/sink/'.$Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl), 'SinkDiscussion') . '</li>';
      
               // Close discussion
               if ($Session->CheckPermission('Vanilla.Discussions.Close', $Discussion->CategoryID))
                  $Sender->Options .= '<li>'.Anchor($Discussion->Closed == '1' ? 'Reopen' : 'Close', 'vanilla/discussion/close/'.$Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl), 'CloseDiscussion') . '</li>';
               
               // Delete discussion
               if ($Session->CheckPermission('Vanilla.Discussions.Delete', $Discussion->CategoryID))
                  $Sender->Options .= '<li>'.Anchor('Delete', 'vanilla/discussion/delete/'.$Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl), 'DeleteDiscussion') . '</li>';
               
               // Allow plugins to add options
               $Sender->FireEvent('DiscussionOptions');
               
               if ($Sender->Options != '') {
               ?>
               <ul class="Options">
                  <li><strong><?php echo Gdn::Translate('Options'); ?></strong>
                     <ul>
                        <?php echo $Sender->Options; ?>
                     </ul>
                  </li>
               </ul>
               <?php
               }
            }          
         ?>
      </li>
      <?php
      }
      ?>
      <li class="Title">
         <strong><?php
            echo Anchor(Format::Text($Discussion->Name), '/discussion/'.$Discussion->DiscussionID.'/'.Format::Url($Discussion->Name).($Discussion->CountCommentWatch > 0 ? '/#Item_'.$Discussion->CountCommentWatch : ''), 'DiscussionLink');
         ?></strong>
      </li>
      <?php
         $Sender->FireEvent('AfterDiscussionTitle');
      ?>
      <li class="Meta">
         <?php
            echo '<span>';
            echo sprintf(Plural($Discussion->CountComments, '%s comment', '%s comments'), $Discussion->CountComments);
            echo '</span>';
            if ($CountUnreadComments > 0 && $Session->IsValid())
               echo '<strong>',sprintf(Gdn::Translate('%s new'), $CountUnreadComments),'</strong>';
               
            echo '<span>';
            $Last = new stdClass();
            $Last->UserID = $Discussion->LastUserID;
            $Last->Name = $Discussion->LastName;
            printf(Gdn::Translate('Most recent by %1$s %2$s'), UserAnchor($Last), Format::Date($Discussion->LastDate));
            echo '</span>';

            echo Anchor($Discussion->Category, '/categories/'.$Discussion->CategoryUrlCode, 'Category');
            $Sender->FireEvent('DiscussionMeta');
         ?>
      </li>
   </ul>
</li>
<?php
}