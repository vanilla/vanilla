<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();

$Alt = FALSE;
$CurrentOffset = $this->Offset;
foreach ($this->MessageData->Result() as $Message) {
   $CurrentOffset++;
   $Alt = $Alt == TRUE ? FALSE : TRUE;
   $Class = $Alt ? 'Alt' : '';
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
   <a name="Item_<?php echo $CurrentOffset;?>" class="Item" />
   <?php echo UserPhoto($Author, 'Photo'); ?>
   <div>
      <?php
         echo UserAnchor($Author, 'Name');
         echo Format::Date($Message->DateInserted);
      ?>
      <div class="Message"><?php echo Format::To($Message->Body, $Format); ?></div>
   </div>
</li>
<?php }