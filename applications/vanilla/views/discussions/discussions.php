<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
if (!function_exists('WriteDiscussion'))
    include($this->fetchViewLocation('helper_functions', 'discussions', 'vanilla'));

$Alt = '';
if (property_exists($this, 'AnnounceData') && is_object($this->AnnounceData)) {
    foreach ($this->AnnounceData->result() as $Discussion) {
        $Alt = $Alt == ' Alt' ? '' : ' Alt';
        WriteDiscussion($Discussion, $this, $Session, $Alt);
    }
}

$Alt = '';
foreach ($this->DiscussionData->result() as $Discussion) {
    $Alt = $Alt == ' Alt' ? '' : ' Alt';
    WriteDiscussion($Discussion, $this, $Session, $Alt);
}
