<?php if (!defined('APPLICATION')) exit();
echo '<div class="DataListWrap">';
echo '<h2 class="H">'.t('Recent Comments').'</h2>';
echo '<ul class="DataList SearchResults">';
if (sizeof($this->data('Comments'))) {
    foreach ($this->data('Comments') as $Comment) {
        $Permalink = '/discussion/comment/'.$Comment->CommentID.'/#Comment_'.$Comment->CommentID;
        $User = UserBuilder($Comment, 'Insert');
        $this->EventArguments['User'] = $User;
        ?>
        <li id="<?php echo 'Comment_'.$Comment->CommentID; ?>" class="Item">
            <?php $this->fireEvent('BeforeItemContent'); ?>
            <div class="ItemContent">
                <div class="Message"><?php
                    echo SliceString(Gdn_Format::plainText($Comment->Body, $Comment->Format), 250);
                    ?></div>
                <div class="Meta">
                <span class="MItem"><?php echo t('Comment in', 'in').' '; ?>
                    <b><?php echo anchor(Gdn_Format::text($Comment->DiscussionName), $Permalink); ?></b></span>
                    <span class="MItem"><?php printf(t('Comment by %s'), userAnchor($User)); ?></span>
                    <span
                        class="MItem"><?php echo anchor(Gdn_Format::date($Comment->DateInserted), $Permalink); ?></span>
                </div>
            </div>
        </li>
        <?php
    }
} else {
    echo '<li class="Item Empty">'.t('This user has not commented yet.').'</li>';
}
echo '</ul>';
echo '</div>';
