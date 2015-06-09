<?php if (!defined('APPLICATION')) exit(); ?>

<div class="SplashInfo">
    <h1><?php echo $this->data('Message', sprintf(t('Leaving %s'), c('Garden.Title'))); ?></h1>
    <p><?php echo $this->data('Description', t("You will be automatically redirected shortly.")); ?></p>
</div>
