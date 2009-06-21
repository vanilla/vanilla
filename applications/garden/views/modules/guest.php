<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Box GuestBox">
   <h4><?php echo Gdn::Translate('Howdy, Stranger!'); ?></h4>
   <p><?php echo Translate($this->MessageCode); ?></p>
   <p>
      <?php echo Anchor('Sign In', '/entry/?Target='.urlencode($this->_Sender->SelfUrl), 'Button'); ?> 
      <?php echo Anchor('Register For Membership', '/entry/?Target='.urlencode($this->_Sender->SelfUrl), 'Button'); ?>
   </p>
</div>