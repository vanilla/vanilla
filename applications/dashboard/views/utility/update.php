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
    <?php $deletedFiles = $this->data('DeletedFiles', []); ?>
    <?php if ($deletedFiles) : ?>
        <h2><?php echo t('Deleted Files'); ?></h2>
        <p><?php
            echo t('These files have been deleted since the last Vanilla release.').' '
                .t('You may encounter some issues if you do not manually remove them from your installation.');
            ?>
        </p>
        <ul>
        <?php foreach ($deletedFiles as $file) {
            echo wrap($file, 'li');
        } ?>
        </ul>
    <?php endif; ?>
</div>
