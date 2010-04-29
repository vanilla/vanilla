<?php if (!defined('APPLICATION')) exit();
$this->AddSideMenu();
?>
<h2><?php echo T('Import'); ?></h2>
<?php
$CurrentStep = GetValue('CurrentStep', $this->Data, 0);
if($CurrentStep < 1) {
	// The import hasn't started yet.
	echo '<div class="Info">',
		T('Garden.Import.Info', 'You\'ve successfully uploaded an import file.
Please review the information below and click <b>Start Import</b> to begin the import.'),
		  '</div>';
} else {
	// The import is in process.
	echo '<div class="Info">',
		T('Garden.Import.Continue.Description', 'It appears as though you are in the middle of an import.
Please choose one of the following options.'),
		'</div>';
}
?>
<table class="AltColumns">
	<tr>
		<th><?php echo T('Filename') ?></th>
		<td class="Alt"><?php echo htmlentities(GetValue('OriginalFilename', $this->Data)); ?></td>
	</tr>
	<?php
	foreach(GetValue('Header', $this->Data, array()) as $Name => $Value) {
		$Name = htmlentities($Name);
		$Value = htmlentities($Value);
		
		echo "<tr><th>$Name</th><td class=\"Alt\">$Value</td></tr>\n";
	}
	?>
</table>
<?php
	echo Anchor(T($CurrentStep < 1 ? 'Start Import' : 'Continue Import'), 'dashboard/import/go', 'Button'),
		' ',
		Anchor(T('Restart'), 'dashboard/import/restart', 'Button');