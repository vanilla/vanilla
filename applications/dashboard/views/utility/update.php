<?php if (!defined('APPLICATION')) exit(); ?>

<div class="SplashInfo">
    <?php
    if ($this->data('Success')) {
        echo '<h1>', t('Success'), '</h1>';
        echo '<p>', t('The update was successful.'), '</p>';
    } else {
        echo '<h1>', t('Failure'), '</h1>';
        echo '<p>', t('The update was not successful.'), '</p>';
    }
    ?>
    <!--   <p><?php echo t('The page you were looking for could not be found.'); ?></p>-->
</div>
