<?php if (!defined('APPLICATION')) exit();
if (!function_exists('WriteDiscussion'))
   include($this->FetchViewLocation('helper_functions', 'discussions'));
   
WriteDiscussion($Discussion, $this, $Session, '');