<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
if (!function_exists('WriteDiscussion'))
   include($this->FetchViewLocation('helper_functions', 'discussions'));
   
WriteDiscussion($Discussion, $this, $Session, '');