<?php if (!defined('APPLICATION')) exit();

echo $this->Form->open();
?>
    <div class="Title">
        <h1><?php echo t("Vanilla is installed!"); ?></h1>
    </div>
    <div class="Form">
        <ul>
            <li><?php echo anchor(t('Click here to carry on to your dashboard'), 'settings'); ?>.</li>
        </ul>
    </div>
<?php
echo $this->Form->close();
