<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
if (!property_exists($this, 'HideActivity'))
    $this->HideActivity = FALSE;

if (!property_exists($this, 'ProfileUserID'))
    $this->ProfileUserID = '';

if (!function_exists('WriteActivity'))
    include($this->fetchViewLocation('helper_functions', 'activity'));

foreach ($this->data('Activities') as $Activity) {
    if ($this->HideActivity)
        $Activity->ActivityType .= ' Hidden';

    writeActivity($Activity, $this, $Session);
}
