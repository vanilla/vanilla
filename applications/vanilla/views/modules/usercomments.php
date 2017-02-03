<?php if (!defined('APPLICATION')) exit();
$user = $this->data('User');
echo '<div class="DataListWrap">';
echo '<h2 class="H">'.t('Recent Comments').'</h2>';
echo '<ul class="DataList SearchResults">';
if (sizeof($this->data('Comments'))) {
    foreach ($this->data('Comments') as $comment) {
        $permalink = '/discussion/comment/'.$comment->CommentID.'/#Comment_'.$comment->CommentID;
        $this->EventArguments['User'] = $user;
        ?>
        <li id="<?php echo 'Comment_'.$comment->CommentID; ?>" class="Item">
            <?php $this->fireEvent('BeforeItemContent'); ?>
            <div class="ItemContent">
                <div class="Message"><?php
                    echo SliceString(Gdn_Format::plainText($comment->Body, $comment->Format), 250);
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
echo '</ul>';
echo anchor('All Comments', 'profile/comments/'.$user->UserID.'/'.rawurlencode($user->Name));
echo '</div>';
