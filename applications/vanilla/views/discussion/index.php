<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
if (!function_exists('WriteComment'))
    include $this->fetchViewLocation('helper_functions', 'discussion');

// Wrap the discussion related content in a div.
echo '<div class="MessageList Discussion">';

// Write the page title.
echo '<!-- Page Title -->
<div id="Item_0" class="PageTitle">';

echo '<div class="Options">';

$this->fireEvent('BeforeDiscussionOptions');
WriteBookmarkLink();
WriteDiscussionOptions();
WriteAdminCheck();

echo '</div>';

echo '<h1>'.$this->data('Discussion.Name').'</h1>';

echo "</div>\n\n";

$this->fireEvent('AfterDiscussionTitle');
$this->fireEvent('AfterPageTitle');

// Write the initial discussion.
if ($this->data('Page') == 1) {
    include $this->fetchViewLocation('discussion', 'discussion');
    echo '</div>'; // close discussion wrap

    $this->fireEvent('AfterDiscussion');
} else {
    echo '</div>'; // close discussion wrap
}

echo '<div class="CommentsWrap">';

// Write the comments.
$this->Pager->Wrapper = '<span %1$s>%2$s</span>';
echo '<span class="BeforeCommentHeading">';
$this->fireEvent('CommentHeading');
echo $this->Pager->toString('less');
echo '</span>';

echo '<div class="DataBox DataBox-Comments">';
if ($this->data('Comments')->numRows() > 0)
    echo '<h2 class="CommentHeading">'.$this->data('_CommentsHeader', t('Comments')).'</h2>';
?>
    <ul class="MessageList DataList Comments">
        <?php include $this->fetchViewLocation('comments'); ?>
    </ul>
<?php
$this->fireEvent('AfterComments');
if ($this->Pager->LastPage()) {
    $LastCommentID = $this->addDefinition('LastCommentID');
    if (!$LastCommentID || $this->Data['Discussion']->LastCommentID > $LastCommentID)
        $this->addDefinition('LastCommentID', (int)$this->Data['Discussion']->LastCommentID);
    $this->addDefinition('Vanilla_Comments_AutoRefresh', Gdn::config('Vanilla.Comments.AutoRefresh', 0));
}
echo '</div>';

echo '<div class="P PagerWrap">';
$this->Pager->Wrapper = '<div %1$s>%2$s</div>';
echo $this->Pager->toString('more');
echo '</div>';
echo '</div>';

WriteCommentForm();
