<?php if (!defined('APPLICATION')) return; ?>
<h1><?php echo $this->data('Title'); ?></h1>
<div class="Wrap">
    <?php
    echo $this->Form->open();
    echo $this->Form->errors();
    ?>

    <div class="DismissMessage WarningMessage">
        <?php
        echo formatString(t('You are about to unban {User.UserID,user}.'), $this->Data);

        if ($this->data('OtherReasons')) {
            echo "\n".t(
                    'This user is also banned for other reasons and may stay banned.',
                    'This user is also banned for other reasons and may stay banned or become banned again.'
                );
        }
        ?>
    </div>

    <?php

    if ($LogID = $this->data('User.Attributes.BanLogID')) {
        echo '<div class="P">', $this->Form->checkBox('RestoreContent', "Restore deleted content."), '</div>';
    }

    echo '<div class="Buttons P">', $this->Form->button('Unban'), '</div>';
    echo $this->Form->close();
    ?>
</div>
