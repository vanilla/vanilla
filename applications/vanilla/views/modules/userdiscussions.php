<?php if (!defined('APPLICATION')) exit();
require_once Gdn::controller()->fetchViewLocation('helper_functions', 'discussions', 'Vanilla');
$user = $this->data('User');
?>
<div class="DataListWrap">
    <h2 class="H"><?php echo t('Recent Discussions'); ?></h2>
    <ul class="DataList SearchResults">
<?php
if (sizeof($this->data('Discussions'))) {
    foreach ($this->data('Discussions') as $discussion) {
        $permalink = discussionUrl($discussion);
        $this->EventArguments['User'] = $user;
        ?>
        <li id="<?php echo 'Discussion_'.$discussion->DiscussionID; ?>" class="Item">
            <?php $this->fireEvent('BeforeItemContent'); ?>
            <div class="ItemContent">
                <div class="Message"><?php
                    echo '<h2>'.anchor(Gdn_Format::text($discussion->Name), $permalink).'</h2>';
                    echo sliceString(Gdn_Format::plainText($discussion->Body, $discussion->Format), 250);
                    ?></div>
                <div class="Meta">
                <span class="MItem"><?php echo t('Posted in', 'in').' '; ?>
                    <b><?php echo categoryLink($discussion); ?></b></span>
                    <span class="MItem"><?php echo anchor(Gdn_Format::date($discussion->DateInserted), $permalink); ?></span>
                </div>
            </div>
        </li>
        <?php
    }
} else {
    echo '<li class="Item Empty">'.t('This user has not made any discussions yet.').'</li>';
}
?>
</ul>
<?php echo anchor('All Discussions', 'profile/discussions/'.$user->UserID.'/'.rawurlencode($user->Name)); ?>
</div>
