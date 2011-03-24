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
   $Message = nl2br(SliceString(Gdn_Format::Text(Gdn_Format::To($Conversation->LastMessage, $Conversation->Format), FALSE), 100));
   if (StringIsNullOrEmpty(trim($Message)))
      $Message = T('Blank Message');
?>
<li class="<?php echo $CssClass; ?>">
   <?php if ($LastPhoto != '') { ?>
   <div class="Photo"><?php echo $LastPhoto; ?></div>
   <?php } ?>
   <div class="ItemContent Conversation">
      <div class="Excerpt"><?php echo Anchor($Message, '/messages/'.$Conversation->ConversationID.'/#Item_'.$JumpToItem, 'Message'); ?></div>
      <div class="Meta">
         <span><?php echo UserAnchor($LastAuthor, 'Name'); ?></span>
         <span><?php echo Gdn_Format::Date($Conversation->DateLastMessage); ?></span>
         <span><?php printf(Plural($Conversation->CountMessages, '%s message', '%s messages'), $Conversation->CountMessages); ?></span>
         <?php
         if ($Conversation->CountNewMessages > 0) {
            echo '<strong>'.Plural($Conversation->CountNewMessages, '%s new', '%s new').'</strong>';
         }
         ?>
         <span class="MetaItem">
            <span class="MetaLabel"><?php echo T('Participants'); ?></span>
            <span class="MetaValue">
               <?php
               $First = TRUE;
               foreach ($Conversation->Participants as $User) {
                  if ($First)
                     $First = FALSE;
                  else
                     echo ', ';
                  echo UserAnchor($User, 'Name');
               }
               ?>
            <span>
         </span>
      </div>
   </div>
</li>
<?php
}