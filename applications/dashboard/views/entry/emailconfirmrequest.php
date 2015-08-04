<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo t("Confirm Email") ?></h1>
<div>
    <?php
    echo $this->Form->open();
    echo $this->Form->errors();
    echo '<div class="P">';

    if ($this->Form->errorCount() == 0)
        echo t('Your request has been sent.', 'Your request has been sent. Check your email for further instructions.');

    echo '</div>';
    echo $this->Form->close(); ?>
</div>
