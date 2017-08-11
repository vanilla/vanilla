<?php if (!defined('APPLICATION')) exit();
$user = $this->data('User');
?>
<div class="DataListWrap">
    <h2 class="H"><?php echo t('Recent Comments'); ?></h2>
    <ul class="DataList SearchResults">
<?php
if (sizeof($this->data('Comments'))) {
    foreach ($this->data('Comments') as $comment) {
        $permalink = commentUrl($comment);
        $this->EventArguments['User'] = $user;
        ?>
        <li id="<?php echo 'Comment_'.$comment->CommentID; ?>" class="Item">
            <?php $this->fireEvent('BeforeItemContent'); ?>
            <div class="ItemContent">
                <div class="Message">
                    <h2><?php echo anchor(Gdn_Format::text($comment->DiscussionName), $permalink); ?></h2>
                    <?php
                    echo sliceString(Gdn_Format::plainText($comment->Body, $comment->Format), 250);
                    ?></div>
                <div class="Meta">
                <span class="MItem"><?php echo t('Comment in', 'in').' '; ?>
                    <b><?php echo anchor(Gdn_Format::text($comment->DiscussionName), $permalink); ?></b></span>
                    <span class="MItem"><?php printf(t('Comment by %s'), userAnchor($user)); ?></span>
                    <span
                        class="MItem"><?php echo anchor(Gdn_Format::date($comment->DateInserted), $permalink); ?></span>
                </div>
            </div>
        </li>
        <?php
    }
} else {
    echo '<li class="Item Empty">'.t('This user has not commented yet.').'</li>';
}
?>
    </ul>
    <?php echo anchor('All Comments', 'profile/comments/'.$user->UserID.'/'.rawurlencode($user->Name)); ?>
</div>
