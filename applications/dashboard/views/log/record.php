<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php echo $this->data('Title'); ?></h1>
    <div
        class="Info"><?php echo t('Edits and deletions are recorded here. Use &lsquo;Restore&rsquo; to undo any change.');
        echo '<br>'.sprintf(t('We start logging edits on a post %s minutes after it is first created.'), c('Garden.Log.FloodControl', 20)); ?>
    </div>
<?php
echo '<noscript><div class="Errors"><ul><li>', t('This page requires Javascript.'), '</li></ul></div></noscript>';
echo $this->Form->open();
?>
<div class="toolbar flex-wrap">
    <div class="toolbar-buttons">
        <?php
        echo anchor(t('Restore'), '#', ['class' => 'RestoreButton btn btn-primary']);
        echo anchor(t('Delete Forever'), '#', ['class' => 'DeleteButton btn btn-primary']);
        ?>
        <?php PagerModule::write(['Sender' => $this, 'Limit' => 10, 'View' => 'pager-dashboard']); ?>
    </div>
</div>
<?php

echo '<div id="LogTable">';
include __DIR__.'/table.php';
echo '</div>';
?>
<?php
$this->addDefinition('ExpandText', t('more'));
$this->addDefinition('CollapseText', t('less'));
echo $this->Form->close();
