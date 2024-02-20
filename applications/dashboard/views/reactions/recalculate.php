<?php if (!defined('APPLICATION')) exit; ?>
<h1><?php echo $this->data('Title'); ?></h1>
<div class="Wrap">
    <div class="Info">
        <p><?php echo t('Recalculate reaction information.'); ?></p>
        <?php if ($this->data('Recalculated')): ?>
        <p>
            <strong><?php
                echo t('Reactions have been recalculated.');
            ?></strong>
        </p>
        <?php endif; ?>
    </div>
    <div>
        <?php
        echo $this->Form->open();
        echo $this->Form->close('Recalculate Now');
        ?>
    </div>
</div>
