<?php if (!defined('APPLICATION')) return; ?>
<h1><?php echo $this->Data('Title'); ?></h1>
<div class="Wrap">
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>

<div class="Warning">
   <?php
   echo FormatString(T('You are about to unban {User.UserID,user}.'), $this->Data);
   ?>
</div>
   
<?php

if ($LogID = $this->Data('User.Attributes.BanLogID')) {
   echo '<div class="P">', $this->Form->CheckBox('RestoreContent', "Restore deleted content."), '</div>';
}

echo '<div class="Buttons P">', $this->Form->Button('Unban'), '</div>';
echo $this->Form->Close();
?>
</div>