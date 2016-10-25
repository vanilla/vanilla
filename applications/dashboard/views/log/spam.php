<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php echo $this->data('Title'); ?></h1>
<?php

helpAsset($this->data('Title'), t('Content flagged as spam is stored here for moderator review.'));

echo '<noscript><div class="Errors"><ul><li>', t('This page requires Javascript.'), '</li></ul></div></noscript>';
echo $this->Form->open();
?>
<div class="toolbar flex-wrap">
    <div class="toolbar-buttons">
        <?php
        echo anchor(t('Spam'), '#', array('class' => 'SpamButton btn btn-primary'));
        echo anchor(t('Not Spam'), '#', array('class' => 'NotSpamButton btn btn-primary'));
        ?>
    </div>
    <?php PagerModule::write(['Sender' => $this, 'Limit' => 10, 'View' => 'pager-dashboard']); ?>
</div>
<?php

echo '<div id="LogTable">';
include dirname(__FILE__).'/table.php';
echo '</div id="LogTable">';
?>
<?php

$this->addDefinition('ExpandText', t('more'));
$this->addDefinition('CollapseText', t('less'));
echo $this->Form->close();
