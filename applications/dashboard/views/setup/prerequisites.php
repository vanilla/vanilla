<?php if (!defined('APPLICATION')) exit();

echo $this->Form->open();
?>
    <div class="Title">
        <h1>
            <?php echo img('applications/dashboard/design/images/vanilla_logo.png', array('alt' => 'Vanilla')); ?>
            <p><?php echo sprintf(t('Version %s Installer'), APPLICATION_VERSION); ?></p>
        </h1>
    </div>
    <div class="Form">
        <?php
        echo $this->Form->errors();
        ?>
        <div class="Button">
            <?php echo anchor(t('Try Again'), '/dashboard/setup'); ?>
        </div>
    </div>
<?php
echo $this->Form->close();
