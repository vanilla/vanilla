<?php if (!defined('APPLICATION')) exit(); ?>

<div class="SplashInfo">
    <h1><?php echo t('PermissionErrorTitle', 'Permission Problem'); ?></h1>

    <p><?php echo $this->data('Message', t('PermissionErrorMessage', "You don't have permission to do that.")); ?></p>
</div>
