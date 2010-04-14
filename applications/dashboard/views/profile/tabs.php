<?php if (!defined('APPLICATION')) exit();

// Get the tab sort order from the user-prefs.
$SortOrder = FALSE;
$SortOrder = ArrayValue('ProfileTabOrder', $this->User->Preferences, FALSE);
// If not in the user prefs, get the sort order from the application prefs.
if ($SortOrder === FALSE)
   $SortOrder = Gdn::Config('Garden.ProfileTabOrder');

if (!is_array($SortOrder))
   $SortOrder = array();
   
// Make sure that all tabs are present in $SortOrder
foreach ($this->_ProfileTabs as $TabCode => $TabUrl) {
   if (!in_array($TabCode, $SortOrder))
      $SortOrder[] = $TabCode;
}
?>
<div class="Tabs ProfileTabs">
   <ul>
   <?php
   // Get sorted tabs
   foreach ($SortOrder as $TabCode) {
      $CssClass = $TabCode == $this->_CurrentTab ? ' class="Active"' : '';
      if (array_key_exists($TabCode, $this->_ProfileTabs)) // Just in case a method was removed but is still present in sortorder
         echo '<li'.$CssClass.'>'.Anchor($TabCode, $this->_ProfileTabs[$TabCode])."</li>\r\n";

   }
   ?>
   </ul>
</div>