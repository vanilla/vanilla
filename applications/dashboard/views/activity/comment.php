<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
   
if (!property_exists($this, 'ProfileUserID'))
   $this->ProfileUserID = '';
   
if (!function_exists('WriteActivityComment'))
   include($this->FetchViewLocation('helper_functions', 'activity'));

if ($this->Data('Comment'))
   WriteActivityComment($this->Data('Comment'), $this, $Session);
