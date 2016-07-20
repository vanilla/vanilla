<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php echo $this->data('Title'); ?></h1>
<?php

Gdn_Theme::assetBegin('Help');
echo '<h2>'.$this->data('Title').'</h2>';
echo '<p>'.t('Every edit or deletion is recorded here. Use &lsquo;Restore&rsquo; to undo any change.').'</p>';
Gdn_Theme::assetEnd();

echo '<noscript><div class="Errors"><ul><li>', t('This page requires Javascript.'), '</li></ul></div></noscript>';
echo $this->Form->open();
?>
    <div class="toolbar">
        <div class="buttons">
        <?php
        echo anchor(t('Restore'), '#', array('class' => 'RestoreButton btn btn-secondary'));
        echo anchor(t('Delete Forever'), '#', array('class' => 'DeleteButton btn btn-secondary'));
        ?>
        </div>
    </div>
<?php

echo '<div id="LogTable">';
include dirname(__FILE__).'/table.php';
echo '</div id="LogTable">';
?>
<?php

$this->addDefinition('ExpandText', t('(more)'));
$this->addDefinition('CollapseText', t('(less)'));
echo $this->Form->close();
