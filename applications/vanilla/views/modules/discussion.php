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
         if ($Discussion->CountUnreadComments > 0 || $Discussion->CountUnreadComments === '')
            echo '<strong>'.Plural($Discussion->CountUnreadComments, '%s new', '%s new').'</strong>';
            
         $Last = new stdClass();
         $Last->UserID = $Discussion->LastUserID;
         $Last->Name = $Discussion->LastName;
         echo '<span>'.Gdn_Format::Date($Discussion->LastDate).' '.UserAnchor($Last).'</span>';
      ?>
   </div>
</li>
