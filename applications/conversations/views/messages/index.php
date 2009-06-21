<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
?>
<h2><?php
   // Who is in the conversation?
   if ($this->RecipientData->NumRows() == 1) {
      echo Gdn::Translate('Just you!');
   } else if ($this->RecipientData->NumRows() == 2) {
      foreach ($this->RecipientData->Result() as $User) {
         if ($User->UserID != $Session->UserID)
            echo sprintf(Gdn::Translate('%s and you'), UserAnchor($User));
      }
   } else {
      $Users = array();
      foreach ($this->RecipientData->Result() as $User) {
         if ($User->UserID != $Session->UserID)
            $Users[] = UserAnchor($User);
      }
      echo sprintf(Gdn::Translate('%s, and you'), implode(', ', $Users));
   }
?></h2>
<?php
echo $this->Pager->ToString('less');
?>
<ul id="Conversation">
   <?php
if ($this->MessageData->NumRows() == 0) {
   ?>
   <li class="Empty"><?php echo Gdn::Translate('The conversation is empty.'); ?></li>
   <?php
} else {
   $MessagesViewLocation = $this->FetchViewLocation('messages');
   include($MessagesViewLocation);
} ?>
</ul>
<?php
echo $this->Pager->ToString();
echo $this->Form->Open(array('action' => Url('/messages/addmessage/')));
?>
<h3><?php echo Translate("Add Message") ?></h3>
<?php
   echo $this->Form->TextBox('Body', array('MultiLine' => TRUE, 'class' => 'MessageBox'));
   echo $this->Form->Button('Send Message');
   // echo $this->Form->Button('Save Draft');
   // echo $this->Form->Button('Preview');
   echo $this->Form->Close();
