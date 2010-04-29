<?php if (!defined('APPLICATION')) exit();

echo $this->Form->Open();
?>
<div class="Title">
   <h1><?php echo T("Vanilla is installed!"); ?></h1>
</div>
<div class="Form">
   <ul>
      <li>Normally this screen would be here to lead you through how you can upgrade your Vanilla 1 database to Vanilla 2. Sadly, we're not finished developing that part yet. So, instead you can <?php echo Anchor('click here to carry on to your dashboard', 'settings'); ?>.</li>
   </ul>
</div>