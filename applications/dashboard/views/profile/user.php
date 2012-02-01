<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
?>
<div class="User" itemscope itemtype="http://schema.org/Person">
   <h1><?php echo $this->User->Name; ?></h1>
   <?php
   if ($this->User->Admin == 2) {
      echo '<div class="Info">', T('This is a system account and does not represent a real person.'), '</div>';
   }

   if ($this->User->About != '') {
      echo '<div id="Status" itemprop="description">'.Wrap(Gdn_Format::Display($this->User->About));
      if ($this->User->About != '' && ($Session->UserID == $this->User->UserID || $Session->CheckPermission('Garden.Users.Edit')))
         echo ' - ' . Anchor(T('clear'), '/profile/clear/'.$this->User->UserID.'/'.$Session->TransientKey(), 'Change');

      echo '</div>';
   }
   $this->FireEvent('BeforeUserInfo');
   echo Gdn_Theme::Module('UserInfoModule');
   $this->FireEvent('AfterUserInfo');
   ?>
</div>