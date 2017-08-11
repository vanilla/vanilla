<?php if (!defined('APPLICATION')) return; ?>
<script>
    jQuery(document).ready(function($) {
        $('#Form_ReasonText').focus(function() {
            $('#Form_Reason2').prop('checked', true);
        });
    });
</script>

<h1><?php echo $this->data('Title'); ?></h1>
<div class="Wrap">
    <?php
    echo $this->Form->open();
    echo $this->Form->errors();
    ?>

    <div class="DismissMessage WarningMessage">
        <?php
        echo formatString(t('You are about to ban {User.UserID,user}.'), $this->Data);
        ?>
    </div>

    <div class="P"><b><?php echo t('Why are you Banning this user?') ?></b></div>

    <?php

    echo '<div class="P">', $this->Form->radio('Reason', 'Spamming', ['Value' => 'Spam']), '</div>';
    echo '<div class="P">', $this->Form->radio('Reason', 'Abusive Behavior', ['Value' => 'Abuse']), '</div>';
    echo '<div class="P">',
    $this->Form->radio('Reason', 'Other', ['Value' => 'Other']),
    '<div class="TextBoxWrapper">',
    $this->Form->textBox('ReasonText', ['MultiLine' => TRUE]),
    '</div>',
    '</div>';

    if ($this->data('_MayDeleteContent'))
        echo '<div class="P">', $this->Form->checkBox('DeleteContent', t("Also delete this user's content.")), '</div>';

    ?>


    <?php
    echo '<div class="Buttons P">', $this->Form->button(t('Ban.Action', 'Ban')), '</div>';
    echo $this->Form->close();
    ?>
</div>
