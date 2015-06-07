<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Box Moderators">
    <?php echo panelHeading(t('Moderators')); ?>
    <ul class="PanelInfo">
        <?php
        foreach ($this->ModeratorData[0]->Moderators as $Mod) {
            $Mod = UserBuilder($Mod);
            echo '<li>'.UserPhoto($Mod, 'Small').' '.UserAnchor($Mod).'</li>';
        }
        ?>
    </ul>
</div>
