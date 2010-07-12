<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Box GuestBox">
   <h4><?php echo T('Howdy, Stranger!'); ?></h4>
   <p><?php echo T($this->MessageCode, $this->MessageDefault); ?></p>
   <p>
      <?php echo Anchor(T('Sign In'), Gdn::Authenticator()->SignInUrl($this->_Sender->SelfUrl), 'Button'.(C('Garden.SignIn.Popup') ? ' SignInPopup' : '')); ?> 
      <?php
         $Url = Gdn::Authenticator()->RegisterUrl($this->_Sender->SelfUrl);
         if(!empty($Url))
            echo Anchor(T('Apply for Membership'), $Url, 'Button');
      ?>
   </p>
</div>