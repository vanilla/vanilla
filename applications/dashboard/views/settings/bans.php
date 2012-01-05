<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->Data('Title'); ?></h1>
<div class="Info"><?php echo T('You can ban IP addresses, email domains, and words from usernames using this tool.'); ?></div>
<?php
echo '<noscript><div class="Errors"><ul><li>', T('This page requires Javascript.'), '</li></ul></div></noscript>';
echo $this->Form->Open();
?>
<div class="Info">
   <?php
   echo Anchor(T('Add Item'), '/dashboard/settings/bans/add', array('class' => 'SmallButton Add'));
   ?>
</div>
<?php

echo '<div id="BansTable">';
include dirname(__FILE__).'/banstable.php';
echo '</div id="BansTable">';

echo $this->Form->Close();