<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
if (!function_exists('WriteDiscussion'))
    include($this->fetchViewLocation('helper_functions', 'discussions'));

WriteDiscussion($Discussion, $this, $Session, '');
