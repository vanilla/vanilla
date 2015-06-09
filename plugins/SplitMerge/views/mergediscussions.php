<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php echo $this->data('Title'); ?></h1>
<?php
echo $this->Form->open();
echo $this->Form->errors();

$Discussions = $this->data('Discussions');
if (count($Discussions) < 2) {
    echo wrap(t('You have to select at least 2 discussions to merge.'), 'p');
} else {
    echo wrap(t('Choose the main discussion into which all comments will be merged:'), 'p');

    $DefaultDiscussionID = $Discussions[0]['DiscussionID'];
    $RadioData = consolidateArrayValuesByKey($Discussions, 'DiscussionID', 'Name');
    array_map('htmlspecialchars', $RadioData);
    echo '<ul><li>';
    echo $this->Form->RadioList('MergeDiscussionID', $RadioData, array('ValueField' => 'DiscussionID', 'TextField' => 'Name', 'Default' => $DefaultDiscussionID));
    echo '</li></ul>';

    echo '<div class="P">'.
        $this->Form->checkBox('RedirectLink', 'Leave a redirect links from the old discussions.').
        '</div>';

    echo '<div class="Buttons">'.
        $this->Form->button('Merge').
        '</div>';
}
echo $this->Form->close();
