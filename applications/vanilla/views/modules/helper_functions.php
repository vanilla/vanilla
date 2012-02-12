<?php if (!defined('APPLICATION')) exit();

function WriteModuleDiscussion($Discussion) {
?>
<li id="<?php echo 'Bookmark_'.$Discussion->DiscussionID; ?>">
   <strong><?php
      echo Anchor(Gdn_Format::Text($Discussion->Name, FALSE), '/discussion/'.$Discussion->DiscussionID.'/'.Gdn_Format::Url($Discussion->Name).($Discussion->CountCommentWatch > 0 ? '/#Item_'.$Discussion->CountCommentWatch : ''), 'DiscussionLink');
   ?></strong>
   <div class="Meta">
      <?php   
         $Last = new stdClass();
         $Last->UserID = $Discussion->LastUserID;
         $Last->Name = $Discussion->LastName;
         
         if ($Discussion->CountUnreadComments > 0 || $Discussion->CountUnreadComments === '')
            echo '<span class="MItem HasNew">'.Plural($Discussion->CountUnreadComments, '%s new', '%s new').'</span> ';
         
         echo '<span class="MItem">'.Gdn_Format::Date($Discussion->LastDate, 'html').UserAnchor($Last).'</span>';         
      ?>
   </div>
</li>
<?php
}