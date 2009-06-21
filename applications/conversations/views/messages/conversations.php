<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$Alt = FALSE;
foreach ($this->ConversationData->Result() as $Conversation) {
   $Alt = $Alt == TRUE ? FALSE : TRUE;
   $Class = $Alt ? 'Alt' : '';
   if ($Conversation->CountNewMessages > 0)
      $Class .= ' New';
      
   $Class = trim($Class);
?>
<li<?php echo $Class == '' ? '' : ' class="'.$Class.'"'; ?>>
   <ul class="Info">
      <li class="Authors"><?php
         // $CssClass = $Conversation->Starred == '1' ? 'Starred' : 'Star';
         // echo Anchor('<span>*</span>', 'messages/star/'.$Conversation->ConversationID.'/'.$Session->TransientKey(), $CssClass);
         // TODO: LOOP THROUGH ALL AUTHOR NAMES
         $Name = $Session->UserID == $Conversation->LastMessageUserID ? 'You' : $Conversation->LastMessageName;
         echo UserPhoto($Conversation->LastMessageName, $Conversation->LastMessagePhoto); 
         echo Anchor($Name, '/profile/'.Format::Url($Conversation->LastMessageName));
      ?></li>
      <li class="Updated"><?php echo Format::Date($Conversation->DateLastMessage); ?></li>
      <li class="MessageCount"><?php
         printf(Translate(Plural($Conversation->CountMessages, '%s message', '%s messages')), $Conversation->CountMessages);
         if ($Conversation->CountNewMessages > 0) {
            ?><span><?php printf(Gdn::Translate('%s new'), $Conversation->CountNewMessages); ?></span><?php
         }
      ?></li>
   </ul>
   <?php
      $JumpToItem = $Conversation->CountMessages - $Conversation->CountNewMessages;
      echo Anchor(SliceString(Format::Text($Conversation->LastMessage), 100), '/messages/'.$Conversation->ConversationID.'/#Item_'.$JumpToItem, 'Link');
   ?>
</li>
<?php
}