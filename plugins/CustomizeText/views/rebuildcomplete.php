<?php if (!defined('APPLICATION')) exit();
$Locale = Gdn::Locale();
$Definitions = $Locale->GetDeveloperDefinitions();
$CountDefinitions = count($Definitions);
?>
<h1><?php echo T('Customize Text'); ?></h1>
<div class="Info">
   <?php
		echo 'Search complete. There are <strong>'. $CountDefinitions . '</strong> text definitions available for editing.';
		echo Wrap(Anchor('Go edit them now!', 'settings/customizetext'), 'p');
   ?>
</div>