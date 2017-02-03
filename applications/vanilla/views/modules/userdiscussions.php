<?php if (!defined('APPLICATION')) exit();
require_once Gdn::controller()->fetchViewLocation('helper_functions', 'discussions', 'Vanilla');
$user = $this->data('User');
echo '<div class="DataListWrap">';
echo '<h2 class="H">'.t('Recent Discussions').'</h2>';
echo '<ul class="DataList SearchResults">';
if (sizeof($this->data('Discussions'))) {
    foreach ($this->data('Discussions') as $discussion) {
        $permalink = '/discussion/'.$discussion->DiscussionID;
        $this->EventArguments['User'] = $user;
        ?>
        <li id="<?php echo 'Discussion_'.$discussion->DiscussionID; ?>" class="Item">
            <?php $this->fireEvent('BeforeItemContent'); ?>
            <div class="ItemContent">
                <div class="Message"><?php
                    echo '<h2>'.anchor(Gdn_Format::text($discussion->Name), $permalink).'</h2>';
                    echo SliceString(Gdn_Format::plainText($discussion->Body, $discussion->Format), 250);
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
echo '</ul>';
echo anchor('All Discussions', 'profile/discussions/'.$user->UserID.'/'.rawurlencode($user->Name));
echo '</div>';
