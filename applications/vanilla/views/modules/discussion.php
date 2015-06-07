<?php if (!defined('APPLICATION')) exit();
// An individual discussion record for all panel modules to use when rendering a discussion list.
if (!isset($this->Prefix)) {
    $this->Prefix = 'Bookmark';
}

if (!function_exists('WriteModuleDiscussion')) {
    $DiscussionsModule = new DiscussionsModule();
    require_once $DiscussionsModule->fetchViewLocation('helper_functions');
    require_once Gdn::controller()->fetchViewLocation('helper_functions', 'Discussions', 'Vanilla');
}

WriteModuleDiscussion($Discussion, $this->Prefix);
