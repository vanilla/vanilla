<?php use Vanilla\Forum\Modules\FoundationDiscussionsShim;
use Vanilla\Forum\Modules\FoundationShimOptions;

if (!defined('APPLICATION')) exit();

if (FoundationDiscussionsShim::isEnabled()) {
    $hasAnnounceData = property_exists($this, 'AnnounceData') && is_object($this->AnnounceData);
    $announceData = $hasAnnounceData ? $this->AnnounceData->resultArray() : [];
    $regularData = $this->DiscussionData->resultArray();
    $legacyData = array_merge($announceData, $regularData);
    $options = new FoundationShimOptions();
    $options->setIsMainContent($isMainContent ?? false);
    FoundationDiscussionsShim::printLegacyShim($legacyData, $options);
} else {
    $Session = Gdn::session();
    if (!function_exists('writeDiscussion'))
        include($this->fetchViewLocation('helper_functions', 'discussions', 'vanilla'));

    if (property_exists($this, 'AnnounceData') && is_object($this->AnnounceData)) {
        foreach ($this->AnnounceData->result() as $Discussion) {
            writeDiscussion($Discussion, $this, $Session);
        }
    }

    foreach ($this->DiscussionData->result() as $Discussion) {
        writeDiscussion($Discussion, $this, $Session);
    }
}
