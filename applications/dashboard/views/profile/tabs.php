<?php if (!defined('APPLICATION')) exit();

// Get the tab sort order from the user-prefs.
$SortOrder = FALSE;
$SortOrder = arrayValue('ProfileTabOrder', $this->User->Preferences, false);
// If not in the user prefs, get the sort order from the application prefs.
if ($SortOrder === FALSE)
    $SortOrder = Gdn::config('Garden.ProfileTabOrder');

if (!is_array($SortOrder))
    $SortOrder = array();

// Make sure that all tabs are present in $SortOrder
foreach ($this->ProfileTabs as $TabCode => $TabInfo) {
    if (!in_array($TabCode, $SortOrder))
        $SortOrder[] = $TabCode;
}
?>
<div class="Tabs ProfileTabs">
    <ul>
        <?php
        // Get sorted tabs
        foreach ($SortOrder as $TabCode) {
            $CssClass = $TabCode == $this->_CurrentTab ? 'Active ' : '';
            // array_key_exists: Just in case a method was removed but is still present in sortorder
            if (array_key_exists($TabCode, $this->ProfileTabs)) {
                $TabInfo = val($TabCode, $this->ProfileTabs, array());
                $CssClass .= val('CssClass', $TabInfo, '');
                echo '<li'.($CssClass == '' ? '' : ' class="'.$CssClass.'"').'>'.anchor(val('TabHtml', $TabInfo, $TabCode), val('TabUrl', $TabInfo), array('class' => 'TabLink'))."</li>\r\n";
            }
        }
        ?>
    </ul>
</div>
