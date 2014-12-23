<?php if (!defined('APPLICATION')) exit();

echo $this->Form->Open();
echo $this->Form->Errors();
?>

<table id="CssThemes">
<?php
$LastHeading = '';
foreach($this->Data["ThemeSettings"] as $Row) {
	$Split = explode(' ', $Row['Name'], 2);
	if(count($Split) == 1) {
		$Heading = '';
		$Name = $Split[0];
	} else {
		$Heading = $Split[0];
		$Name = $Split[1];
	}
	
	if($Heading != $LastHeading) {
		echo '<thead><tr><th colspan="3"><h2>'.$Heading.'</h2></th></tr></thead>';
		$LastHeading = $Heading;
	}
?>
	<tr class="ColorRow">
		<td><?php echo T($Name); ?></td>
		<td>
			<div class="ColorPicker" style="background-color: <?php echo $Row['Setting']; ?>">
				&nbsp;
			</div>
		</td>
		<td>
			<?php
				echo $this->Form->Input('Name[]', 'hidden', array('value' => $Row['Name']));
				echo $this->Form->Input('Setting[]', 'text', array('value' => $Row['Setting'], 'class' => 'Setting'));
			?>
		</td>
	</tr>
<?php
}
?>
</table>
<?php
echo $this->Form->Close("Save");
?>