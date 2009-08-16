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
   $MessagesViewLocation = $this->FetchViewLocation('messages');
   include($MessagesViewLocation);
   ?>
</ul>
<?php
echo $this->Pager->ToString();
echo $this->Form->Open(array('action' => Url('/messages/addmessage/')));
echo $this->Form->Label('Add Message', 'Body');
echo $this->Form->TextBox('Body', array('MultiLine' => TRUE, 'class' => 'MessageBox'));
echo $this->Form->Button('Send Message');
echo $this->Form->Close();
