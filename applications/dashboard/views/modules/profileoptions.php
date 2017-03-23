<?php if (!defined('APPLICATION')) exit();
/** Displays the "Edit My Profile" or "Back to Profile" buttons on the top of the profile page. */
?>
<div class="ProfileOptions">
    <?php
    if (Gdn::controller()->EditMode)  {
        echo anchor(t('Back to Profile'), userUrl(Gdn::controller()->User), ['class' => 'ProfileButtons']);
    } else {
        echo buttonGroup($this->data('MemberOptions'), 'NavButton MemberButtons').' ';
        echo $this->data('ProfileOptionsDropdown');
    }
    ?>
</div>
