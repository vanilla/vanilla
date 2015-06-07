<?php if (!defined('APPLICATION')) exit(); ?>

<div class="Center SplashInfo">
    <h1><?php echo $this->data('Message', t('Page Not Found')); ?></h1>

    <div
        id="Message"><?php echo $this->data('Description', t('The page you were looking for could not be found.')); ?></div>

    <!-- Domain: <?php echo Gdn::config('Garden.Domain', ''); ?> -->
</div>
