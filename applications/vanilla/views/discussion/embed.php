<?php if (!defined('APPLICATION')) exit();
$Discussion = $this->data('Discussion');
$ForeignSource = $this->data('ForeignSource');
$SortComments = $this->data('SortComments');
$Comments = $this->data('Comments', false);
$HasCommentData = $Comments !== FALSE;
$Session = Gdn::session();
if (!function_exists('WriteComment'))
    include($this->fetchViewLocation('helper_functions', 'discussion'));

?>
<div class="Embed">
    <?php
    echo '<span class="BeforeCommentHeading">';
    $this->fireEvent('CommentHeading');
    echo '</span>';

    if ($SortComments == 'desc')
        writeEmbedCommentForm();
    else if ($HasCommentData && $Comments->numRows() > 0)
        echo wrap(t('Comments'), 'h2');
    ?>
    <ul class="DataList MessageList Comments">
        <?php
        if ($HasCommentData) {
            $this->fireEvent('BeforeCommentsRender');
            $CurrentOffset = $this->Offset;
            foreach ($Comments as $Comment) {
                ++$CurrentOffset;
                $this->CurrentComment = $Comment;
                writeComment($Comment, $this, $Session, $CurrentOffset);
            }
        }
        ?>
    </ul>
    <?php
    if ($HasCommentData) {
        if ($this->Pager->lastPage()) {
            $LastCommentID = $this->addDefinition('LastCommentID');
            if (!$LastCommentID || $this->Data['Discussion']->LastCommentID > $LastCommentID)
                $this->addDefinition('LastCommentID', (int)$this->Data['Discussion']->LastCommentID);
            $this->addDefinition('Vanilla_Comments_AutoRefresh', Gdn::config('Vanilla.Comments.AutoRefresh', 0));
        }

        // Send the user to the discussion in the forum when paging
        if (c('Garden.Embed.PageToForum') && $this->Pager->hasMorePages()) {
            $DiscussionUrl = discussionUrl($Discussion).'#latest';
            echo '<div class="PageToForum Foot">';
            echo anchor(t('More Comments'), $DiscussionUrl);
            echo '</div>';
        } else
            echo $this->Pager->toString('more');
    }

    if ($SortComments != 'desc')
        writeEmbedCommentForm();

    ?>
</div>
