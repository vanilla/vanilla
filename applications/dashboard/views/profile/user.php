<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
?>
<div class="User" itemscope itemtype="http://schema.org/Person">
   <h1 class="H"><?php 
      echo htmlspecialchars($this->User->Name);
      
      echo '<span class="Gloss">';
      if ($this->User->Title)
         echo ' '.Bullet().' '.Wrap(htmlspecialchars($this->User->Title), 'span', array('class' => 'User-Title'));
      
         $this->FireEvent('UsernameMeta');
      echo '</span>';
   ?></h1>
   <?php
   if ($this->User->Admin == 2) {
      echo '<div class="Info">', T('This is a system account and does not represent a real person.'), '</div>';
   }

   if ($this->User->About != '') {
      echo '<div id="Status" itemprop="description">'.Wrap(Gdn_Format::Display($this->User->About));
      if ($this->User->About != '' && ($Session->UserID == $this->User->UserID || $Session->CheckPermission('Garden.Users.Edit')))
         echo ' - ' . Anchor(T('clear'), '/profile/clear/'.$this->User->UserID.'/'.$Session->TransientKey(), 'Hijack');

      echo '</div>';
   }
   $this->FireEvent('BeforeUserInfo');
   echo Gdn_Theme::Module('UserInfoModule');
   $this->FireEvent('AfterUserInfo');
   ?>
</div>