<?php if (!defined('APPLICATION')) return; ?>
<h1><?php echo $this->Data('Title'); ?></h1>
<div class="Wrap">
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>

<div class="DismissMessage WarningMessage">
   <?php
   echo FormatString(T('You are about to unban {User.UserID,user}.'), $this->Data);

   if ($this->Data('OtherReasons')) {
      echo "\n".T('This user is also banned for other reasons and may stay banned.');
   }
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
