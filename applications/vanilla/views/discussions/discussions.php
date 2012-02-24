<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
if (!function_exists('WriteDiscussion'))
   include($this->FetchViewLocation('helper_functions', 'discussions', 'vanilla'));

$Alt = '';
if (property_exists($this, 'AnnounceData') && is_object($this->AnnounceData)) {
	foreach ($this->AnnounceData->Result() as $Discussion) {
		$Alt = $Alt == ' Alt' ? '' : ' Alt';
		WriteDiscussion($Discussion, $this, $Session, $Alt);
	}
}

$Alt = '';
foreach ($this->DiscussionData->Result() as $Discussion) {
   $Alt = $Alt == ' Alt' ? '' : ' Alt';
   WriteDiscussion($Discussion, $this, $Session, $Alt);
}