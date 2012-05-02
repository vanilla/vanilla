<?php if (!defined('APPLICATION')) exit();

function WriteModuleDiscussion($Discussion, $Px = 'Bookmark') {
?>
<li id="<?php echo "{$Px}_{$Discussion->DiscussionID}"; ?>" class="<?php echo CssClass($Discussion); ?>">
   <span class="Options">
      <?php
//      echo OptionsList($Discussion);
      echo BookmarkButton($Discussion);
      ?>
   </span>
   <div class="Title"><?php
      echo Anchor(Gdn_Format::Text($Discussion->Name, FALSE), DiscussionUrl($Discussion).($Discussion->CountCommentWatch > 0 ? '#Item_'.$Discussion->CountCommentWatch : ''), 'DiscussionLink');
   ?></div>
   <div class="Meta">
      <?php   
         $Last = new stdClass();
         $Last->UserID = $Discussion->LastUserID;
         $Last->Name = $Discussion->LastName;
         
         echo NewComments($Discussion);
         
         echo '<span class="MItem">'.Gdn_Format::Date($Discussion->LastDate, 'html').UserAnchor($Last).'</span>';         
      ?>
   </div>
</li>
<?php
}