<?php if (!defined('APPLICATION')) exit();

$Forms = $this->Data('Forms');
// Loop through the form collection and write out the handles
$FormToggleMenu = new ToggleMenuModule();
foreach ($Forms as $Form) {
	$Code = GetValue('Name', $Form);
   $Active = strtolower($Code) == strtolower($this->Data('CurrentFormName'));
   if ($Active)
      $FormToggleMenu->CurrentLabelCode($Code);
   
   $FormToggleMenu->AddLabel(GetValue('Label', $Form), $Code);
}
echo $FormToggleMenu->ToString();

// Now loop through the form collection and dump the forms
foreach ($Forms as $Form) {
	$Name = GetValue('Name', $Form);
	$Active = strtolower($Name) == strtolower($this->Data('CurrentFormName'));
	$Url = GetValue('Url', $Form);
	echo '<div class="Toggle-'.$Name.($Active ? ' Active' : '').' FormWrap">';
		// echo ProxyRequest(Url($Url.'?DeliveryType=VIEW', TRUE));
		echo '<div class="Popin" rel="'.Url($Url.'?DeliveryType=VIEW', TRUE).'"></div>';
	echo '</div>';
}
