<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo $this->data('Title'); ?></h1>

<?php
echo $this->Form->open();
echo $this->Form->errors();
?>
<ul>
    <li>
        <div class="Warning"><?php echo sprintf(t('Are you sure you want to delete this %s?'), strtolower(t('Pocket'))); ?></div>
    </li>
</ul>
<?php echo $this->Form->close('Delete');