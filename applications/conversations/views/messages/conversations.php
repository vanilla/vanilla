<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$Alt = FALSE;
foreach ($this->ConversationData->Result() as $Conversation) {
   $Alt = $Alt == TRUE ? FALSE : TRUE;
   $LastAuthor = UserBuilder($Conversation, 'LastMessage');
   $LastPhoto = UserPhoto($LastAuthor, 'Photo');
   $CssClass = 'Item';
   $CssClass .= $Alt ? ' Alt' : '';
   $CssClass .= $Conversation->CountNewMessages > 0 ? ' New' : '';
   $CssClass .= $LastPhoto != '' ? ' HasPhoto' : '';
   $JumpToItem = $Conversation->CountMessages - $Conversation->CountNewMessages;
?>
<li class="<?php echo $CssClass; ?>">
   <?php if ($LastPhoto != '') { ?>
   <div class="Photo"><?php echo $LastPhoto; ?></div>
   <?php } ?>
   <div class="ItemContent Conversation">
      <?php echo UserAnchor($LastAuthor, 'Name Title'); ?>
      <div class="Excerpt"><?php echo Anchor(SliceString(Format::Text($Conversation->LastMessage), 100), '/messages/'.$Conversation->ConversationID.'/#Item_'.$JumpToItem, 'Message'); ?></div>
      <div class="Meta">
         <span><?php echo Format::Date($Conversation->DateLastMessage); ?></span>
         <span><?php printf(T(Plural($Conversation->CountMessages, '%s message', '%s messages')), $Conversation->CountMessages); ?></span>
         <?php
         if ($Conversation->CountNewMessages > 0) {
            echo '<strong>'.sprintf(T('%s new'), $Conversation->CountNewMessages).'</strong>';
         }
         ?>
      </div>
   </div>
</li>
<?php
}