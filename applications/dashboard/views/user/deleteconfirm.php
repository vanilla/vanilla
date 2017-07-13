<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php printf(t('Delete User: %s'), userAnchor($this->User)); ?></h1>
<?php
echo $this->Form->open(['class' => 'User']);
echo $this->Form->errors();
?>
<div class="alert alert-danger padded">
    <?php echo t('This action cannot be undone.'); ?>
</div>
<div class="padded">
    <?php printf(t("By clicking the button below, you will be deleting the user account for %s forever."), wrap(htmlspecialchars($this->User->Name), 'strong')); ?>
    <?php
    if ($this->Method == 'keep')
        echo t("The user content will remain untouched.");
    else if ($this->Method == 'wipe')
        echo t("All of the user content will be replaced with a message stating the user has been deleted.");
    else
        echo t("The user content will be completely deleted.");
    ?>
</div>
<?php
echo $this->Form->close('Delete User Forever');
