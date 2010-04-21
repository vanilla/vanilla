<?php if (!defined('APPLICATION')) exit(); ?>
<h1>Database Structure Upgrades</h1>
<div class="Info"><?php echo T($this->Data['Status']); ?></div>
<?php

if(array_key_exists('CapturedSql', $this->Data)) {
	$CapturedSql = (array)$this->Data['CapturedSql'];
	$Url = 'dashboard/utility/structure/'.$this->Data['ApplicationName'].'/0/'.(int)$this->Data['Drop'].'/'.(int)$this->Data['Explicit'];
	
	if(count($CapturedSql) > 0) {
	?>
		<table class="AltRows">
			<tbody>
				<?php
				$Alt = TRUE;
				foreach($this->Data['CapturedSql'] as $Sql) {
					$Alt = $Alt == TRUE ? FALSE : TRUE;
				?>
				<tr<?php echo $Alt ? ' class="Alt"' : ''; ?>>
					<td><pre><?php echo $Sql; ?></pre></td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
		<?php
	} else if($this->Data['CaptureOnly']) {
		?>
		<div class="Info"><?php echo T('There are no database structure changes required. There may, however, be data changes.'); ?></div>
		<?php
	}
	echo Anchor(T('Run structure & data scripts'), $Url, 'Button', array('style' => 'font-size: 16px;'));
}