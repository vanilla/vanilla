<?php if (!defined('APPLICATION')) exit();

$titleSuffix = '';
if ($this->data('UserRangeWarning')) {
    $titleSuffix = '<span class="text-warning form-control-sm">'.$this->data('UserRangeWarning').'</span>';
}

$userBoard = new TableSummaryModule(t('Active Users').$titleSuffix);
$userBoard->addColumn('users', t('Name'), [], TableSummaryModule::MAIN_CSS_CLASS)
    ->addColumn('count-comments', t('Comments'), ['class' => 'column-xs']);

foreach ($this->data('UserData') as $userdata) {
    $id = val('UserID', $userdata);
    $user = Gdn::userModel()->getID($id);
    $name = val('Name', $user);
    $userBlock = new MediaItemModule(val('Name', $user), userUrl($user), '', 'div');
    $userBlock->setView('media-sm')
        ->setImage(userPhotoUrl($user))
        ->addMeta(Gdn_Format::date(val('DateLastActive', $user), 'html'));
    $userBoard->addRow([
        'users' => $userBlock,
        'count-comments' => number_format($user->CountComments)
    ]);
}
echo $userBoard;

$discussionBoard = new TableSummaryModule(t('Popular Discussions'));
$discussionBoard->addColumn('discussion', t('Title'), ['class' => 'column-xs'], TableSummaryModule::MAIN_CSS_CLASS)
    ->addColumn('count-comments', t('Comments'), ['class' => 'column-xs'])
    ->addColumn('count-bookmarks', t('Follows'), ['class' => 'column-xs'])
    ->addColumn('count-views', t('Views'), ['class' => 'column-xs']);

foreach ($this->Data['DiscussionData'] as $discussion) {
    $discussionBlock = new MediaItemModule(htmlspecialchars($discussion->Name), discussionUrl($discussion), '', 'div');
    $discussionBlock->setView('media-sm')
        ->addMeta(Gdn_Format::date($discussion->DateInserted, 'html'));
    $discussionBoard->addRow([
        'discussion' => $discussionBlock,
        'count-comments' => number_format($discussion->CountComments),
        'count-bookmarks' => number_format($discussion->CountBookmarks),
        'count-views' => number_format($discussion->CountViews)
    ]);
}

echo $discussionBoard;
