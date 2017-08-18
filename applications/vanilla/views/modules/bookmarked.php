<?php if (!defined('APPLICATION')) exit();
require_once $this->fetchViewLocation('helper_functions');
require_once Gdn::controller()->fetchViewLocation('helper_functions', 'Discussions', 'Vanilla');

$Bookmarks = $this->data('Bookmarks');
?>
<div id="Bookmarks" class="Box BoxBookmarks">
    <h4><?php echo t('Bookmarked Discussions'); ?></h4>
    <?php if (count($Bookmarks->result()) > 0): ?>

        <ul id="<?php echo $this->ListID; ?>" class="PanelInfo PanelDiscussions DataList">
            <?php
            foreach ($Bookmarks->result() as $Discussion) {
                writeModuleDiscussion($Discussion);
            }
            if ($Bookmarks->numRows() == $this->Limit) {
                ?>
                <li class="ShowAll"><?php echo anchor(t('All Bookmarks'), 'discussions/bookmarked'); ?></li>
            <?php } ?>
        </ul>

    <?php else: ?>
        <div class="P PagerWrapper">
            <?php
            echo sprintf(
                t('Click the %s beside discussions to bookmark them.'),
                '<a href="javascript: void(0);" class="Bookmark"> </a>');
            ?>
        </div>
    <?php endif; ?>
</div>
