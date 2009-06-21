<?php if (!defined('APPLICATION')) exit();

echo $this->Form->Open();
?>
<div class="Title">
   <h1><span>Vanilla</span></h1>
   <h2><?php echo Gdn::Translate("Vanilla is installed!"); ?></h2>
</div>
<p>
   Normally this screen would be here to lead you through how you can upgrade your Vanilla 1 database to Vanilla 2. Sadly, we're not finished developing that part yet. So, instead you can 
   <?php
   // echo Gdn::Translate("We see you've got some old Vanilla 1 tables in your database, ");
   // echo Anchor('garden/import', 'click here to import them');
   ?>
<?php echo Anchor('settings', 'click here to carry on to your dashboard'); ?></p>