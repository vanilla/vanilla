<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();

if (!property_exists($this, 'ProfileUserID'))
    $this->ProfileUserID = '';

if (!function_exists('WriteActivityComment'))
    include($this->fetchViewLocation('helper_functions', 'activity'));

if ($this->data('Comment'))
    WriteActivityComment($this->data('Comment'), $this, $Session);
