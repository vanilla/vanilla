<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
?>
<div class="User">
   <h1><?php echo $this->User->Name; ?></h1>
   <?php
   if ($Session->UserID == $this->User->UserID) {
      echo $this->AboutForm->Open(array('class' => 'About'));
      echo $this->AboutForm->TextBox('About', array('value' => $this->User->About));
      echo $this->AboutForm->Button(Gdn::Translate('Save'));
      echo $this->AboutForm->Close();
   } elseif (!StringIsNullOrEmpty($this->User->About)) {
      echo '<h4>'.$this->User->About.'</h4>';
   }
?>
</div>