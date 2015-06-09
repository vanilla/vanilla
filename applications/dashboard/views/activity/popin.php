<?php if (!defined('APPLICATION')) exit(); ?>
<ul class="PopList Activities">
    <?php
    if (count($this->data('Activities'))):
        ?>
        <li class="Item Title"><?php
            echo anchor(t('Notification Preferences'), 'profile/preferences');
            echo wrap(t('Notifications'), 'strong');
            ?></li>
        <?php foreach ($this->data('Activities') as $Activity): ?>
        <li class="Item">
            <?php
            if ($Activity['Photo']) {
                $PhotoAnchor = anchor(
                    img($Activity['Photo'], array('class' => 'ProfilePhoto PhotoWrapMedium')),
                    $Activity['PhotoUrl'], 'PhotoWrap PhotoWrapMedium');
            } else {
                $PhotoAnchor = '';
            }
            ?>
            <div class="Author Photo"><?php echo $PhotoAnchor; ?></div>
            <div class="ItemContent Activity">
                <?php echo $Activity['Headline']; ?>
                <div class="Meta">
                    <span class="MItem DateCreated"><?php echo Gdn_Format::date($Activity['DateUpdated']); ?></span>
                </div>
            </div>
        </li>
    <?php endforeach; ?>
        <li class="Item Center">
            <?php
            echo anchor(sprintf(t('All %s'), t('Notifications')), '/profile/notifications');
            ?>
        </li>
    <?php else: ?>
        <li class="Item Empty Center"><?php echo t('You do not have any notifications yet.'); ?></li>
    <?php endif; ?>
</ul>
