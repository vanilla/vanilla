<?php if (!defined('APPLICATION')) exit();

$Forms = $this->data('Forms');
// Loop through the form collection and write out the handles
$FormToggleMenu = new ToggleMenuModule();
foreach ($Forms as $Form) {
    $Code = val('Name', $Form);
    $Active = strtolower($Code) == strtolower($this->data('CurrentFormName'));
    if ($Active)
        $FormToggleMenu->CurrentLabelCode($Code);

    $FormToggleMenu->AddLabel(val('Label', $Form), $Code);
}
echo $FormToggleMenu->toString();

// Now loop through the form collection and dump the forms
foreach ($Forms as $Form) {
    $Name = val('Name', $Form);
    $Active = strtolower($Name) == strtolower($this->data('CurrentFormName'));
    $Url = val('Url', $Form);
    echo '<div class="Toggle-'.$Name.($Active ? ' Active' : '').' FormWrap">';
    // echo ProxyRequest(url($Url.'?DeliveryType=VIEW', true));
    echo '<div class="Popin" rel="'.Url($Url.'?DeliveryType=VIEW', true).'"></div>';
    echo '</div>';
}
