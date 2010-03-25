<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$Alt = FALSE;
foreach ($this->ConversationData->Result() as $Conversation) {
   $Alt = $Alt == TRUE ? FALSE : TRUE;
   $Class = $Alt ? 'Alt' : '';
   if ($Conversation->CountNewMessages > 0)
      $Class .= ' New';
      
   if ($Conversation->LastMessagePhoto != '')
      $Class .= ' HasPhoto';
      
   $Class = trim($Class);
   $Name = $Session->UserID == $Conversation->LastMessageUserID ? 'You' : $Conversation->LastMessageName;
   $JumpToItem = $Conversation->CountMessages - $Conversation->CountNewMessages;
?>
<li<?php echo $Class == '' ? '' : ' class="'.$Class.'"'; ?>>
   <?php
   $LastAuthor = UserBuilder($Conversation, 'LastMessage');
   echo UserPhoto($LastAuthor, 'Photo');
   ?>
   <div>
      <?php
      echo UserAnchor($LastAuthor, 'Name');
      echo Anchor(SliceString(Format::Text($Conversation->LastMessage), 100), '/messages/'.$Conversation->ConversationID.'/#Item_'.$JumpToItem, 'Message');
      echo '<div class="Meta">';
         echo Format::Date($Conversation->DateLastMessage);
         echo '<span>&bull;</span>';
         printf(Gdn::Translate(Plural($Conversation->CountMessages, '%s message', '%s messages')), $Conversation->CountMessages);
         if ($Conversation->CountNewMessages > 0) {
            echo '<span>&bull;</span>';
            echo '<em>';
            printf(Gdn::Translate('%s new'), $Conversation->CountNewMessages);
            echo '</em>';
         }
      echo '</div>';
      ?>
   </div>
</li>
<?php
}