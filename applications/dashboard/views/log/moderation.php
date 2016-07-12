<?php if (!defined('APPLICATION')) exit(); ?>
<?php
Gdn_Theme::assetBegin('Help');
echo '<h2>'.$this->data('Title').'</h2>';
echo t('To prevent abuse, some tools automatically hide content and list it here until it is manually approved by a moderator.');
Gdn_Theme::assetEnd();
echo '<noscript><div class="Errors"><ul><li>', t('This page requires Javascript.'), '</li></ul></div></noscript>';
echo $this->Form->open();
?>
<div class="header-block">
    <h1><?php echo $this->data('Title'); ?></h1>
</div>
<div class="toolbar">
    <div class="buttons">
        <?php
        echo anchor(t('Approve'), '#', array('class' => 'RestoreButton btn btn-secondary'));
        echo anchor(t('Delete Forever'), '#', array('class' => 'DeleteButton btn btn-secondary'));
        ?>
    </div>
    <div class="search"><?php
        if (c('Vanilla.Categories.Use')) {
            echo '<div class="input-wrap input-wrap-multiple">';
            echo $this->Form->CategoryDropDown('CategoryID', [
                    'Value' => val('ModerationCategoryID', $this->Data),
                    'IncludeNull' => t('Show all categories')]
            );
            echo anchor(t('Filter'), '#', array('class' => 'FilterButton btn btn-primary'));
            echo '</div>';
        }
        ?></div>
    <?php PagerModule::write(array('Sender' => $this, 'Limit' => 10, 'View' => 'pager-dashboard')); ?>
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
