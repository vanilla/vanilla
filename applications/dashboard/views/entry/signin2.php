<?php if (!defined('APPLICATION')) exit();

$Methods = $this->Data('Methods', array());
$SelectedMethod = $this->Data('SelectedMethod', array());

// Render the tabs for all of the methods.
echo '<div class="SignInMethods">';
foreach ($Methods as $Key => $Method) {
   $Selected = $Key == $SelectedMethod;
   
   echo '<div class="Method SignIn_'.$Key.'">';

   echo $this->FetchView($Method['ViewLocation']);

   echo '</div>';
}
echo '</div>';

// Render the buttons to select between the tabs.
echo '<ul class="Tabs">';

foreach ($Methods as $Key => $Method) {
   echo '<li>',
      Anchor($Method['Label'], $Method['Url']),
      '</li>';
}

echo '</ul>';