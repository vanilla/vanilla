<?php if (!defined('APPLICATION')) exit(); ?>
<h1>
    <?php echo $this->data('Title'); ?>
</h1>
<p>
    <?php echo sprintf(
        t('You are now leaving %1$s. Click the link to continue to %2$s.'),
        c('Garden.Title', ''),
        $this->data('Target')
    ); ?>
</p>
