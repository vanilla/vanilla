<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php echo $this->data('Title'); ?></h1>
<?php
Gdn_Theme::assetBegin('Help');
echo '<h2>'.$this->data('Title').'</h2>';
echo '<p>'.t('Content flagged as spam is stored here for moderator review.').'</p>';
Gdn_Theme::assetEnd();
echo '<noscript><div class="Errors"><ul><li>', t('This page requires Javascript.'), '</li></ul></div></noscript>';
echo $this->Form->open();
?>
    <div class="Info">
        <?php
        echo anchor(t('Spam'), '#', array('class' => 'SpamButton SmallButton'));
        echo anchor(t('Not Spam'), '#', array('class' => 'NotSpamButton SmallButton'));
        ?>
    </div>
<?php

echo '<div id="LogTable">';
include dirname(__FILE__).'/table.php';
echo '</div id="LogTable">';
?>
    <div class="Info">
        <?php
        echo anchor(t('Spam'), '#', array('class' => 'SpamButton SmallButton'));
        echo anchor(t('Not Spam'), '#', array('class' => 'NotSpamButton SmallButton'));
        ?>
    </div>
<?php

$this->addDefinition('ExpandText', t('(more)'));
$this->addDefinition('CollapseText', t('(less)'));
echo $this->Form->close();
