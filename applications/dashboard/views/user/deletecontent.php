<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo $this->Data('Title'); ?></h1>
<div class="Wrap">
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>

<div class="Warning">
   <?php
   echo FormatString(T("You are about to delete all of a user's content.", "You are about to delete all of the content for {User.UserID,user}."), $this->Data);
   ?>
</div>
<?php

echo '<div class="Buttons Buttons-Confirm">';
echo $this->Form->Button('Yes');
echo $this->Form->Button('No', array('type' => 'button', 'class' => 'Button Close'));
echo '</div>';

echo $this->Form->Close();
?>   
</div>