<?php if (!defined('APPLICATION')) exit();

foreach ($this->DraftData->resultArray() as $Draft) {
    $Offset = val('CountComments', $Draft, 0);
    if ($Offset > c('Vanilla.Comments.PerPage', 30)) {
        $Offset -= c('Vanilla.Comments.PerPage', 30);
    } else {
        $Offset = 0;
    }

    $draftID = val('DraftID', $Draft);
    $discussionID = val('DiscussionID', $Draft);
    $excerpt = sliceString(Gdn_Format::plainText(val('Body', $Draft), val('Format', $Draft)), 200) ?: t('(No Body)');

    $isDiscussion = (!is_numeric($discussionID) || $discussionID <= 0);
    $orphaned = !val('DiscussionExists', $Draft);

    $editUrl = ($isDiscussion || $orphaned) ? '/post/editdiscussion/0/'.$draftID : '/discussion/'.$discussionID.'/'.$Offset.'/#Form_Comment';
    $deleteUrl = 'vanilla/drafts/delete/'.$draftID.'/'.Gdn::session()->transientKey().'?Target='.urlencode('/drafts/'.$this->offset);
    $deleteText = t('Draft.Delete', '&times;');
    $deleteContent = \Gdn::themeFeatures()->useDataDrivenTheme() ? '&times;' : $deleteText;
    ?>
    <li class="Item Draft pageBox">
        <div
            class="Options"><?php
                echo anchor($deleteContent, $deleteUrl, 'Delete', ['title' => $deleteText]); ?></div>
        <div class="ItemContent">
            <?php
            $anchorText = htmlspecialchars(val('Name', $Draft));
            if (!empty($anchorText)) {
                echo anchor(wrap($anchorText, "span", ['class' => 'Draft-Title', 'role' => 'heading', 'aria-level' => '2']), $editUrl, 'Title DraftLink');
            } else if (!$isDiscussion) {
                echo anchor(wrap(t('(Untitled)'), "h2"), $editUrl);
            }
            ?>
            <div class="Excerpt">
                <?php echo anchor($excerpt, $editUrl); ?>
            </div>
        </div>
    </li>
<?php
}
