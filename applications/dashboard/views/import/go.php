<?php if (!defined('APPLICATION')) exit();
$this->AddSideMenu();
?>
<style> .Complete { text-decoration: line-through; }</style>
<h2><?php echo T('Import'); ?></h2>
<?php
echo $this->Form->Errors();
$CurrentStep = GetValue('CurrentStep', $this->Data, 0);
$Steps = GetValue('Steps', $this->Data, array());

if($CurrentStep > 0 && !array_key_exists($CurrentStep, $Steps)) {
	echo '<div class="Info">',
		T('Import.Complete', 'Your import is complete.'),
		'</div>';
}
?>

<ol>
<?php
foreach($Steps as $Number => $Name) {
	echo '<li ', ($CurrentStep > $Number ? 'class="Complete"' : ''), '>',
		T('Garden.Import.Steps.'.$Name, _SpacifyCamelCase($Name));
	
	if($Number == $CurrentStep) {
		$Message = GetValue('CurrentStepMessage', $this->Data);
		if($Message)
			echo '<div>', $Message, '</div>';
	}
		
	echo '</li>';
}

/**
 * Add spaces to a camel case word by putting a space before every capital letter.
 */
function _SpacifyCamelCase($Str) {
	$Result = '';
	for($i = 0; $i < strlen($Str); $i++) {
		$c = substr($Str, $i, 1);
		if($Result && strtoupper($c) === $c && strtoupper($Str[$i - 1]) != $Str[$i - 1])
			$Result .= ' ';
		$Result .= $c;
	}
	return $Result;
}
?>
</ol>
<?php
	if(array_key_exists($CurrentStep, $this->Data['Steps'])) {
		echo '<noscript><div>',
			Anchor(T('Continue'), strtolower($this->Application).'/import/go', 'Button'),
			'</div></noscript>';
	}