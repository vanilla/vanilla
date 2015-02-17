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

/**
 * Generates html output of $Content array
 *
 * @param array|object $Content
 * @param PromotedContentModule $Sender
 */
function WritePromotedContent($Content, $Sender) {
   static $UserPhotoFirst = NULL;
   if ($UserPhotoFirst === NULL)
      $UserPhotoFirst = C('Vanilla.Comment.UserPhotoFirst', TRUE);

   $ContentType = val('RecordType', $Content);
   $ContentID = val("{$ContentType}ID", $Content);
   $Author = val('Author', $Content);

   switch (strtolower($ContentType)) {
      case 'comment':
         $ContentURL = CommentUrl($Content);
         break;
      case 'discussion':
         $ContentURL = DiscussionUrl($Content);
         break;
   }
   $Sender->EventArguments['Content'] = &$Content;
   $Sender->EventArguments['ContentUrl'] = &$ContentURL;
?>
   <div id="<?php echo "Promoted_{$ContentType}_{$ContentID}"; ?>" class="<?php echo CssClass($Content); ?>">
      <div class="AuthorWrap">
         <span class="Author">
            <?php
            if ($UserPhotoFirst) {
               echo UserPhoto($Author);
               echo UserAnchor($Author, 'Username');
            } else {
               echo UserAnchor($Author, 'Username');
               echo UserPhoto($Author);
            }
            $Sender->FireEvent('AuthorPhoto');
            ?>
         </span>
         <span class="AuthorInfo">
            <?php
            echo ' '.WrapIf(htmlspecialchars(val('Title', $Author)), 'span', array('class' => 'MItem AuthorTitle'));
            echo ' '.WrapIf(htmlspecialchars(val('Location', $Author)), 'span', array('class' => 'MItem AuthorLocation'));
            $Sender->FireEvent('AuthorInfo');
            ?>
         </span>
      </div>
      <div class="Meta CommentMeta CommentInfo">
         <span class="MItem DateCreated">
            <?php echo Anchor(Gdn_Format::Date($Content['DateInserted'], 'html'), $ContentURL, 'Permalink', array('rel' => 'nofollow')); ?>
         </span>
         <?php
         // Include source if one was set
         if ($Source = GetValue('Source', $Content))
            echo Wrap(sprintf(T('via %s'), T($Source.' Source', $Source)), 'span', array('class' => 'MItem Source'));

         $Sender->FireEvent('ContentInfo');
         ?>
      </div>
      <div class="Title"><?php echo Anchor(Gdn_Format::Text(SliceString($Content['Name'], $Sender->TitleLimit), FALSE), $ContentURL, 'DiscussionLink'); ?></div>
      <div class="Body">
      <?php
         echo Anchor(strip_tags(Gdn_Format::To(SliceString($Content['Body'], $Sender->BodyLimit), $Content['Format'])), $ContentURL, 'BodyLink');
         $Sender->FireEvent('AfterBody'); // seperate event to account for less space.
      ?>
      </div>
   </div>
<?php
}
