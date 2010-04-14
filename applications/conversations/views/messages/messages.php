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
      
   $Class = trim($Class);
   $Format = empty($Message->Format) ? 'Display' : $Message->Format;
   $Author = UserBuilder($Message, 'Insert');
?>
<li id="<?php echo $Message->MessageID; ?>"<?php echo $Class == '' ? '' : ' class="'.$Class.'"'; ?>>
   <div class="ConversationMessage">
      <div class="Meta">
         <span class="Author">
            <?php
            echo UserPhoto($Author, 'Photo');
            echo UserAnchor($Author, 'Name');
            ?>
         </span>
         <span class="DateCreated"><?php echo Gdn_Format::Date($Message->DateInserted); ?></span>
         <span class="ItemLink"><a name="Item_<?php echo $CurrentOffset;?>" class="Item"></a></span>
      </div>
      <div class="Message"><?php echo Gdn_Format::To($Message->Body, $Format); ?></div>
   </div>
</li>
<?php }