<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
?>
<div class="User">
   <h1><?php echo $this->User->Name; ?></h1>
   <?php
      if ($this->User->About != '') {
         echo '<div id="Status"><strong>'.$this->User->About.'</strong>';
         if ($Session->UserID == $this->User->UserID && $this->User->About != '')
            echo ' - ' . Anchor('Clear', '/profile/clear/'.$Session->TransientKey());
            
         echo '</div>';
      }
   ?>
</div>