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
      
   $Class = trim($Class);
   $Format = empty($Message->Format) ? 'Display' : $Message->Format;
?>
<li id="<?php echo $Message->MessageID; ?>"<?php echo $Class == '' ? '' : ' class="'.$Class.'"'; ?>>
   <a name="Item_<?php echo $CurrentOffset;?>" />
   <ul class="Info">
      <li class="Author"><?php
         echo UserPhoto($Message->InsertName, $Message->InsertPhoto); 
         echo UserAnchor($Message->InsertName);
      ?></li>
      <li class="Inserted"><?php echo Format::Date($Message->DateInserted); ?></li>
   </ul>
   <div class="Message"><?php echo Format::To($Message->Body, $Format); ?></div>
</li>
<?php }