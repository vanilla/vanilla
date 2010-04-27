<?php if (!defined('APPLICATION')) exit();
$this->AddSideMenu();
?>
<h2><?php echo T('Import'); ?></h2>
<?php
echo '<div class="Info">',
	T('Garden.Import.Continue.Description', 'It appears as though you are in the middle of an import.
Please choose one of the following options.'),
	'</div>';

echo '<p>',
	Anchor(T('Garden.Import.Continue', 'Continue the import from it\'s current step.'), 'dashboard/import/go'),
	'</p>';
	
echo '<p>',
	Anchor(T('Garden.Import.Restart', 'Restart the import from the beginning.'), 'dashboard/import/index/restart'),
	'</p>';
?>