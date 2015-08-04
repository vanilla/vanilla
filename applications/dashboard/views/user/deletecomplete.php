<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo t('User Deleted'); ?></h1>
<div class="Info">
    <?php echo t("The user has been deleted."); ?>
    <br/><?php echo anchor(t('Back to all users'), '/user'); ?>
</div>
