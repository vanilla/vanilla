<?php if (!defined('APPLICATION')) exit();

echo $this->Form->Open();
?>
<div class="Title">
   <h1><?php echo T("Vanilla is installed!"); ?></h1>
</div>
<div class="Form">
   <ul>
      <li><?php echo Anchor(T('Click here to carry on to your dashboard'), 'settings'); ?>.</li>
   </ul>
</div>
<?php
echo $this->Form->Close();