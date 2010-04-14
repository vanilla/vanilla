<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
?>
<div class="User">
   <h1><?php echo $this->User->Name; ?></h1>
   <?php
      if ($this->User->About != '') {
         echo '<div id="Status">'.Gdn_Format::Display($this->User->About);
         if ($this->User->About != '' && ($Session->UserID == $this->User->UserID || $Session->CheckPermission('Garden.Users.Edit')))
            echo ' - ' . Anchor(T('Clear'), '/profile/clear/'.$this->User->UserID.'/'.$Session->TransientKey(), 'Change');
            
         echo '</div>';
      }
   ?>
</div>