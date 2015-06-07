<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo t("Confirm Email") ?></h1>
<div>
    <?php
    echo $this->Form->open();
    echo $this->Form->errors();

    echo '<div class="P NoPanel">';

    if ($this->data('EmailConfirmed')) {
        echo t('Your email has been successfully confirmed.');
    } else {
        echo sprintf(t('To send another confirmation email click <a href="%s">here</a>.'), url('/entry/emailconfirmrequest'));
    }

    echo '</div>';

    echo $this->Form->close(); ?>
</div>
