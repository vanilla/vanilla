<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
if (!function_exists('writeDiscussion'))
    include($this->fetchViewLocation('helper_functions', 'discussions'));

writeDiscussion($Discussion, $this, $Session);
