<?php if (!defined('APPLICATION')) exit();

// Make sure to force this form to post to the correct place in case the view is
// rendered within another view (ie. /garden/entry/index/):
echo $this->Form->Open();
?>
<h1><?php echo Translate("Reset my password") ?></h1>
<?php echo $this->Form->Errors(); ?>
<ul>
   <li>
      <?php
         echo $this->Form->Label('New Password', 'Password');
         echo $this->Form->Input('Password', 'password');
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Confirm Password', 'Confirm');
         echo $this->Form->Input('Confirm', 'password');
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Button('Save your password →');
      ?>
   </li>
</ul>
<?php echo $this->Form->Close(); ?>