<?php if (!defined('APPLICATION')) exit();

$Controller = Gdn::Controller();
$Session = Gdn::Session();

// Get the tab sort order from the user-prefs.
//$SortOrder = FALSE;
//$SortOrder = ArrayValue('ProfileTabOrder', $Controller->User->Preferences, FALSE);
// If not in the user prefs, get the sort order from the application prefs.
//if ($SortOrder === FALSE)
$SortOrder = C('Garden.ProfileTabOrder');

if (!is_array($SortOrder))
   $SortOrder = array();
   
// Make sure that all tabs are present in $SortOrder
foreach ($Controller->ProfileTabs as $TabCode => $TabInfo) {
   if (!in_array($TabCode, $SortOrder))
      $SortOrder[] = $TabCode;
}
?>