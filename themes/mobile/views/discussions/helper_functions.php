<?php if (!defined('APPLICATION')) exit();

function WriteDiscussion($Discussion, &$Sender, &$Session, $Alt) {
   $CssClass = 'Item';
   $CssClass .= $Discussion->Bookmarked == '1' ? ' Bookmarked' : '';
   $CssClass .= $Alt.' ';
   $CssClass .= $Discussion->Announce == '1' ? ' Announcement' : '';
   $CssClass .= $Discussion->Closed == '1' ? ' Closed' : '';
   $CssClass .= $Discussion->InsertUserID == $Session->UserID ? ' Mine' : '';
   $CssClass .= ($Discussion->CountUnreadComments > 0 && $Session->IsValid()) ? ' New' : '';
   $Sender->EventArguments['Discussion'] = &$Discussion;
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
      if ($Discussion->FirstPhoto != '') {
         if (strtolower(substr($Discussion->FirstPhoto, 0, 7)) == 'http://' || strtolower(substr($Discussion->FirstPhoto, 0, 8)) == 'https://') { 
            $PhotoUrl = $Discussion->FirstPhoto;
         } else {
            $PhotoUrl = 'uploads/'.ChangeBasename($Discussion->FirstPhoto, 'n%s');
         }
         echo Img($PhotoUrl, array('alt' => $Discussion->FirstName));
		}
   ?>
   <div class="ItemContent Discussion">
      <?php echo Anchor($DiscussionName, '/discussion/'.$Discussion->DiscussionID.'/'.Gdn_Format::Url($Discussion->Name).($Discussion->CountCommentWatch > 0 && C('Vanilla.Comments.AutoOffset') ? '/#Item_'.$Discussion->CountCommentWatch : ''), 'Title'); ?>
      <?php $Sender->FireEvent('AfterDiscussionTitle'); ?>
      <div class="Meta">
         <span class="Author"><?php echo $Discussion->FirstName; ?></span>
         <?php
            echo '<span class="Counts'.($Discussion->CountUnreadComments > 0 ? ' NewCounts' : '').'">'
               .($Discussion->CountUnreadComments > 0 ? $Discussion->CountUnreadComments.'/' : '')
               .$Discussion->CountComments
            .'</span>';
            if ($Discussion->LastCommentID != '')
               echo '<span class="LastCommentBy">'.sprintf(T('Latest %1$s'), $Discussion->LastName).'</span> ';
               
            echo '<span class="LastCommentDate">'.Gdn_Format::Date($Discussion->FirstDate).'</span> ';
         ?>
      </div>
   </div>
</li>
<?php
}

// These options do not appear in mobile.
function WriteFilterTabs($Sender) {}
function WriteOptions($Discussion, &$Sender, &$Session) {}