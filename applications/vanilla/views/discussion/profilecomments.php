<?php if (!defined('APPLICATION')) exit();

if (!function_exists('replaceCommentBlockQuotes')) :
    /**
     * Replace <blockquote> in comments body with (Quote)
     *
     * @param $comment - The comment object
     *
     * @return string
     */
    function replaceCommentBlockQuotes($comment) {
        $reg = '/<blockquote(.*)<\/blockquote>/s';
        $parsedBody = Gdn::formatService()->renderHTML($comment->Body, $comment->Format, ['recordID' => $comment->CommentIDm, 'recordType' => 'comment']);
        return preg_replace($reg, "\n(Quote)\n", $parsedBody);
    }
endif;

foreach ($this->data('Comments') as $Comment) {
    $Permalink = commentUrl($Comment);
    $User = userBuilder($Comment, 'Insert');
    $this->EventArguments['User'] = $User;
    ?>
    <li id="<?php echo 'Comment_'.$Comment->CommentID; ?>" class="Item pageBox">
        <?php $this->fireEvent('BeforeItemContent'); ?>
        <div class="ItemContent">
            <div class="Message">
                <?php echo htmlspecialchars(Gdn::formatService()->renderExcerpt($Comment->Body, $Comment->Format)); ?>
            </div>
            <div class="Meta">
                <span class="MItem"><?php echo t('Comment in', 'in').' '; ?>
                    <b><?php echo anchor(Gdn_Format::text($Comment->DiscussionName), $Permalink, '', ['rel' => 'nofollow']); ?></b></span>
                <span class="MItem"><?php printf(t('Comment by %s'), userAnchor($User)); ?></span>
                <span class="MItem"><?php echo anchor(Gdn_Format::date($Comment->DateInserted), $Permalink); ?></span>
            </div>
        </div>
    </li>
<?php
}
