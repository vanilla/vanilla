<?php if (!defined('APPLICATION')) exit(); ?>
<p>
    <?php echo sprintf(
        t('You are now leaving %1$s. Click the link to continue to %2$s.'),
        c('Garden.Title', ''),
        anchor($this->data('Target'), $this->data('Target'))
    ); ?>
</p>
