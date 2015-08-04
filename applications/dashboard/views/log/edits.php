<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php echo $this->data('Title'); ?></h1>
    <div
        class="Info"><?php echo t('Every edit or deletion is recorded here. Use &lsquo;Restore&rsquo; to undo any change.'); ?></div>
<?php
echo '<noscript><div class="Errors"><ul><li>', t('This page requires Javascript.'), '</li></ul></div></noscript>';
echo $this->Form->open();
?>
    <div class="Info">
        <?php
        echo anchor(t('Restore'), '#', array('class' => 'RestoreButton SmallButton'));
        echo anchor(t('Delete Forever'), '#', array('class' => 'DeleteButton SmallButton'));
        ?>
    </div>
<?php

echo '<div id="LogTable">';
include dirname(__FILE__).'/table.php';
echo '</div id="LogTable">';
?>
    <div class="Info">
        <?php
        echo anchor(t('Restore'), '#', array('class' => 'RestoreButton SmallButton'));
        echo anchor(t('Delete Forever'), '#', array('class' => 'DeleteButton SmallButton'));
        ?>
    </div>
<?php

$this->addDefinition('ExpandText', t('(more)'));
$this->addDefinition('CollapseText', t('(less)'));
echo $this->Form->close();
