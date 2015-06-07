<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
$ShowOptions = TRUE;
$Alt = '';
foreach ($this->DraftData->result() as $Draft) {
    $Offset = val('CountComments', $Draft, 0);
    if ($Offset > c('Vanilla.Comments.PerPage', 30)) {
        $Offset -= c('Vanilla.Comments.PerPage', 30);
    } else {
        $Offset = 0;
    }

    $EditUrl = !is_numeric($Draft->DiscussionID) || $Draft->DiscussionID <= 0 ? '/post/editdiscussion/0/'.$Draft->DraftID : '/discussion/'.$Draft->DiscussionID.'/'.$Offset.'/#Form_Comment';
    $Alt = $Alt == ' Alt' ? '' : ' Alt';
    $Excerpt = SliceString(Gdn_Format::text($Draft->Body), 200);
    ?>
    <li class="Item Draft<?php echo $Alt; ?>">
        <div
            class="Options"><?php echo anchor(t('Draft.Delete', 'Delete'), 'vanilla/drafts/delete/'.$Draft->DraftID.'/'.$Session->TransientKey().'?Target='.urlencode($this->SelfUrl), 'Delete'); ?></div>
        <div class="ItemContent">
            <?php echo anchor(Gdn_Format::text($Draft->Name, false), $EditUrl, 'Title DraftLink'); ?>

            <?php if ($Excerpt): ?>
                <div class="Excerpt">
                    <?php echo anchor($Excerpt, $EditUrl); ?>
                </div>
            <?php endif; ?>

        </div>
    </li>
<?php
}
