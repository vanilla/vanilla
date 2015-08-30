<?php if (!defined('APPLICATION')) exit(); ?>

<div class="SplashInfo">
    <?php
    if ($this->data('Success')) {
        echo '<h1>', t('Alive'), '</h1>';
        echo '<p>', t('Everything is ok.'), '</p>';
    }
    ?>
    <!--   <p><?php echo t('The page you were looking for could not be found.'); ?></p>-->
</div>
