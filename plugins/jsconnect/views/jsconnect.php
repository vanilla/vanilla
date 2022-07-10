<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Center jsConnect-Connecting" style="margin-top: 25%">
    <div class="Connect-Wait Hidden">
        <h1><?php echo t('Please wait...'); ?></h1>
        <div class="Progress"></div>
    </div>
    <?php
    echo $this->Form->open(['id' => 'Form_JsConnect-Connect']);
    echo $this->Form->errors();
    echo $this->Form->close();
    ?>
</div>
