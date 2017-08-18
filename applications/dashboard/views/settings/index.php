<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo t('Dashboard Home'); ?></h1>
<?php $this->renderAsset('Messages');

$leaderboard = new TableSummaryModule(t('Active Users'));
$leaderboard->addColumn('users', t('Name'), [], TableSummaryModule::MAIN_CSS_CLASS)
    ->addColumn('count-comments', t('Comments'), ['class' => 'column-xs']);

foreach ($this->ActiveUserData as $userdata) {
    $id = val('UserID', $userdata);
    $user = Gdn::userModel()->getID($id);
    $name = val('Name', $user);
    $userBlock = new MediaItemModule(val('Name', $user), userUrl($user), '', 'div');
    $userBlock->setView('media-sm')
        ->setImage(userPhotoUrl($user))
        ->addMeta(Gdn_Format::date(val('DateLastActive', $user), 'html'));
    $leaderboard->addRow([
        'users' => $userBlock,
        'count-comments' => number_format($user->CountComments)
    ]);
}
?>

<div class="summaries">
    <?php echo $leaderboard; ?>
</div>
<div class="summaries">
    <div class="ReleasesColumn">
        <div class="table-summary-title"><?php echo t('Updates'); ?></div>
        <div class="List"></div>
    </div>
    <div class="NewsColumn">
        <div class="table-summary-title"><?php echo t('Recent News'); ?></div>
        <div class="List"></div>
    </div>
</div>

