<?php if (!defined('APPLICATION')) exit(); ?>
<style> .Complete { text-decoration: line-through; }</style>
<h2><?php echo T('Import'); ?></h2>
<ol>
<?php
	$CurrentStep = $this->Data['CurrentStep'];
	foreach($this->Data['Steps'] as $Number => $Name) {
		echo '<li ', ($CurrentStep > $Number ? 'class="Complete"' : ''), '>',
			T('Garden.Import.Steps.'.$Name);
		
		if($Number == $CurrentStep) {
			$Message = GetValue('CurrentStepMessage', $this->Data);
			if($Message)
				echo '<div>', $Message, '</div>';
		}
			
		echo '</li>';
	}
?>
</ol>