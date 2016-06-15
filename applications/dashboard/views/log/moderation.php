<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php echo $this->data('Title'); ?></h1>
<?php
Gdn_Theme::assetBegin('Help');
echo '<h2>'.$this->data('Title').'</h2>';
echo t('To prevent abuse, some tools automatically hide content and list it here until it is manually approved by a moderator.');
Gdn_Theme::assetEnd();
echo '<noscript><div class="Errors"><ul><li>', t('This page requires Javascript.'), '</li></ul></div></noscript>';
echo $this->Form->open();
?>
    <div class="row form-group"><?php
        if (c('Vanilla.Categories.Use')) {
            echo wrap(sprintf(t('Vanilla.Moderation.FilterBy', 'Show moderation queue for %1$s'), ''), 'div', ['class' => 'label-wrap']);
            echo '<div class="input-wrap input-wrap-multiple">';
            echo $this->Form->CategoryDropDown('CategoryID', [
                    'Value' => val('ModerationCategoryID', $this->Data),
                    'IncludeNull' => 'Everything']
            );
            echo anchor(t('Filter'), '#', array('class' => 'btn btn-primary'));
            echo '</div>';
        }
        ?></div>
    <?php PagerModule::write(array('Sender' => $this, 'Limit' => 10, 'View' => 'pager-dashboard')); ?>
    <div class="Info">
        <?php
        echo anchor(t('Approve'), '#', array('class' => 'RestoreButton SmallButton'));
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
        echo anchor(t('Approve'), '#', array('class' => 'RestoreButton SmallButton'));
        echo anchor(t('Delete Forever'), '#', array('class' => 'DeleteButton SmallButton'));
        ?>
    </div>
<?php

$this->addDefinition('ExpandText', t('(more)'));
$this->addDefinition('CollapseText', t('(less)'));
echo $this->Form->close();
