<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();

$Alt = FALSE;
$CurrentOffset = $this->Offset;
foreach ($this->MessageData->Result() as $Message) {
   $CurrentOffset++;
   $Alt = $Alt == TRUE ? FALSE : TRUE;
   $Class = 'Item';
   $Class .= $Alt ? ' Alt' : '';
   if ($this->Conversation->DateLastViewed < $Message->DateInserted)
      $Class .= ' New';
   
   if ($Message->InsertUserID == $Session->UserID)
      $Class .= ' Mine';
      
   if ($Message->InsertPhoto != '')
      $Class .= ' HasPhoto';
      
   $Format = empty($Message->Format) ? 'Display' : $Message->Format;
   $Author = UserBuilder($Message, 'Insert');

   $this->EventArguments['Message'] = &$Message;
   $this->EventArguments['Class'] = &$Class;
   $this->FireEvent('BeforeConversationMessageItem');
   $Class = trim($Class);
?>
<li id="Message_<?php echo $Message->MessageID; ?>"<?php echo $Class == '' ? '' : ' class="'.$Class.'"'; ?>>
   <div id="Item_<?php echo $CurrentOffset ?>" class="ConversationMessage">
      <div class="Meta">
         <span class="Author">
            <?php
            echo UserPhoto($Author, 'Photo');
            echo UserAnchor($Author, 'Name');
            ?>
         </span>
         <span class="MItem DateCreated"><?php echo Gdn_Format::Date($Message->DateInserted); ?></span>
      </div>
      <div class="Message">
         <?php
         $this->FireEvent('BeforeConversationMessageBody');
         echo Gdn_Format::To($Message->Body, $Format);
         ?>
      </div>
   </div>
</li>
<?php }