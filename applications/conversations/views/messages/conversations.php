<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$Alt = FALSE;
$SubjectsVisible = C('Conversations.Subjects.Visible');

foreach ($this->ConversationData->Result() as $Conversation) {
   $Alt = $Alt == TRUE ? FALSE : TRUE;
   $LastAuthor = UserBuilder($Conversation, 'LastMessage');
   $LastPhoto = UserPhoto($LastAuthor, 'Photo');
   $CssClass = 'Item';
   $CssClass .= $Alt ? ' Alt' : '';
   $CssClass .= $Conversation->CountNewMessages > 0 ? ' New' : '';
   $CssClass .= $LastPhoto != '' ? ' HasPhoto' : '';
   $JumpToItem = $Conversation->CountMessages - $Conversation->CountNewMessages;
   if ($Conversation->Format == 'Text')
      $Message = (SliceString(Gdn_Format::To($Conversation->LastMessage, $Conversation->Format), 100));
   else
      $Message = (SliceString(Gdn_Format::Text(Gdn_Format::To($Conversation->LastMessage, $Conversation->Format), FALSE), 100));

   if (StringIsNullOrEmpty(trim($Message)))
      $Message = T('Blank Message');


   $this->EventArguments['Conversation'] = $Conversation;
?>
<li class="<?php echo $CssClass; ?>">
   <?php
   $Names = '';
   $PhotoUser = NULL;
   foreach ($Conversation->Participants as $User) {
      if (GetValue('UserID', $User) == Gdn::Session()->UserID)
         continue;
      $Names = ConcatSep(', ', $Names, GetValue('Name', $User));
      if (!$PhotoUser && GetValue('Photo', $User))
         $PhotoUser = $User;
   }
   ?>
   <div class="ItemContent Conversation">
      <?php
      $Url = '/messages/'.$Conversation->ConversationID.'/#Item_'.$JumpToItem;

      if ($Names) {
         echo '<h3 class="Users">';
         
         if ($PhotoUser) {
            echo '<div class="Author Photo">'.UserPhoto($PhotoUser).'</div>';
         }

         echo Anchor(htmlspecialchars($Names), $Url), '</h3>';
      }
      if ($SubjectsVisible && $Subject = GetValue('Subject', $Conversation)) {
         echo '<div class="Subject"><b>'.Anchor(htmlspecialchars($Subject), $Url).'</b></div>';
      }
      ?>
      <div class="Excerpt"><?php echo Anchor($Message, $Url, 'Message'); ?></div>
      <div class="Meta">
         <?php 
         $this->FireEvent('BeforeConversationMeta');

         echo '<span class="MetaItem">'.sprintf(Plural($Conversation->CountMessages, '%s message', '%s messages'), $Conversation->CountMessages).'</span>';

         if ($Conversation->CountNewMessages > 0) {
            echo '<strong class="MetaItem">'.Plural($Conversation->CountNewMessages, '%s new', '%s new').'</strong>';
         }
         
         echo '<span class="MetaItem">'.Gdn_Format::Date($Conversation->DateLastMessage).'</span>';
         ?>
      </div>
   </div>
</li>
<?php
}