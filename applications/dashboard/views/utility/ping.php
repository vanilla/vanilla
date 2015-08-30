<?php if (!defined('APPLICATION')) exit(); ?>

<div class="SplashInfo">
    <?php
    if ($this->data('valid')) {
        echo '<h1>', t('Pong'), '</h1>';
    }
    ?>
</div>
