<?php if (!defined('APPLICATION')) exit();
echo Wrap(T($this->Data('Title')), 'h1');

echo $this->Form->Errors();

if($this->Data['Status'])
   echo '<div class="Info">', T($this->Data['Status']), '</div>';

if(array_key_exists('CapturedSql', $this->Data)) {
	$CapturedSql = (array)$this->Data['CapturedSql'];
	$Url = 'dashboard/utility/structure/'.$this->Data['ApplicationName'].'/0/'.(int)$this->Data['Drop'].'/'.(int)$this->Data['Explicit'];
	
	if(count($CapturedSql) > 0) {
	?>
   <div class="Info"><?php echo T('The following structure changes are required for your database.'); ?></div>
		<table class="AltRows">
			<tbody>
				<?php
				$Alt = TRUE;
				foreach($this->Data['CapturedSql'] as $Sql) {
					$Alt = $Alt == TRUE ? FALSE : TRUE;
				?>
				<tr<?php echo $Alt ? ' class="Alt"' : ''; ?>>
					<td><pre><?php
                  $Sql = trim($Sql);
                  if (substr_compare($Sql, ';', -1) != 0)
                     $Sql .= ';';
                  echo $Sql; 
               ?></pre></td>
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
	echo Anchor(T('Run structure & data scripts'), $Url, 'Button', array('style' => 'font-size: 16px;')),
      ' ',
      Anchor(T('Rescan'), 'dashboard/utility/structure/all', 'Button', array('style' => 'font-size: 16px;'));
}