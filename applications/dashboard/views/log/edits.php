<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php echo $this->data('Title'); ?></h1>
<?php

helpAsset($this->data('Title'), t('Every edit or deletion is recorded here. Use &lsquo;Restore&rsquo; to undo any change.'));

echo '<noscript><div class="Errors"><ul><li>', t('This page requires Javascript.'), '</li></ul></div></noscript>';
echo $this->Form->open();
?>
    <div class="toolbar flex-wrap">
        <div class="toolbar-buttons">
        <?php
        echo anchor(t('Restore'), '#', ['class' => 'RestoreButton btn btn-primary']);
        echo anchor(t('Delete Forever'), '#', ['class' => 'DeleteButton btn btn-primary']);
        ?>
        </div>
        <?php PagerModule::write(['Sender' => $this, 'Limit' => 10, 'View' => 'pager-dashboard']); ?>
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
