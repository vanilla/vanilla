<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Box Moderators">
    <?php echo panelHeading(t('Moderators')); ?>
    <ul class="PanelInfo">
        <?php
        $moderators = $this->data('Moderators', []);
        foreach ($moderators as $user) {
            $photo = userPhoto($user, 'Small');
            $anchor = userAnchor($user);
            echo "<li>{$photo} {$anchor}</li>";
        }
        ?>
    </ul>
</div>
