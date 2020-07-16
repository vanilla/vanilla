<?php if (!defined('APPLICATION')) exit();
echo $this->Form->open();
echo $this->Form->errors();
echo heading(t('Spoof'));
?>
<ul>
   <li class="form-group">
      <?php
      echo $this->Form->labelWrap('Username or UserID to Spoof', 'UserReference');
      echo $this->Form->textBoxWrap('UserReference');
      ?>
   </li>
   <li class="form-group">
      <?php
      echo $this->Form->labelWrap('Your Email', 'Email');
      echo $this->Form->textBoxWrap('Email');
      ?>
   </li>
   <li class="form-group">
      <?php echo $this->Form->labelWrap('Your Password', 'Password'); ?>
      <div class="input-wrap">
         <?php echo $this->Form->input('Password', 'password'); ?>
      </div>
   </li>
</ul>
<?php echo $this->Form->close('Go'); ?>
