<?php if (!defined('APPLICATION')) exit(); ?>
<h1>Database Structure</h1>
<?php
if($this->Data['Status']) {
	echo '<div class="Info">', $this->Data['Status'], '</div>';
}

if(array_key_exists('CapturedSql', $this->Data)) {
	$CapturedSql = (array)$this->Data['CapturedSql'];
	
	if(count($CapturedSql) > 0) {
		echo '<div class="Info"><pre>', "\n";
	
		foreach($this->Data['CapturedSql'] as $Sql) {
			echo $Sql, "\n\n";
		}
	
		echo '</pre></div>';
		$Url = Url('garden/utility/structure/'.$this->Data['ApplicationName'].'/0/'.(int)$this->Data['Drop'].'/'.(int)$this->Data['Explicit']);
		echo "<a href='$Url'>Click here to make this change.</a>";
	} elseif($this->Data['CaptureOnly']) {
		echo '<div class="Info">The database structure does not have to be updated.</div>';
	}
}
?>