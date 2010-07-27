<?php if (!defined('APPLICATION')) exit();
// An individual discussion record for all panel modules to use when rendering a discussion list.
?>
<li id="<?php echo 'Bookmark_'.$Discussion->DiscussionID; ?>">
   <strong><?php
      echo Anchor(Gdn_Format::Text($Discussion->Name, FALSE), '/discussion/'.$Discussion->DiscussionID.'/'.Gdn_Format::Url($Discussion->Name).($Discussion->CountCommentWatch > 0 ? '/#Item_'.$Discussion->CountCommentWatch : ''), 'DiscussionLink');
   ?></strong>
   <div class="Meta">
      <?php
         echo '<span>'.$Discussion->CountComments.'</span>';
         $CountUnreadComments = $Discussion->CountComments - $Discussion->CountCommentWatch;
         // Logic for incomplete comment count.
         if($Discussion->CountCommentWatch == 0 && $DateLastViewed = GetValue('DateLastViewed', $Discussion)) {
            if(Gdn_Format::ToTimestamp($DateLastViewed) >= Gdn_Format::ToTimestamp($Discussion->LastDate)) {
               $CountUnreadComments = 0;
               $Discussion->CountCommentWatch = $Discussion->CountComments;
            } else {
               $CountUnreadComments = '';
            }
         }

         if ($CountUnreadComments > 0 || $CountUnreadComments === '')
            echo '<strong>'.Plural($CountUnreadComments, '%s new', '%s new').'</strong>';
            
         $Last = new stdClass();
         $Last->UserID = $Discussion->LastUserID;
         $Last->Name = $Discussion->LastName;
         echo '<span>'.Gdn_Format::Date($Discussion->LastDate).' '.UserAnchor($Last).'</span>';
      ?>
   </div>
</li>
