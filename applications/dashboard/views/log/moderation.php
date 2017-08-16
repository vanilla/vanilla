<?php if (!defined('APPLICATION')) exit(); ?>
<?php

$desc = t('To prevent abuse, some tools automatically hide content and list it here until it is manually approved by a moderator.');
helpAsset($this->data('Title'), $desc);

echo '<noscript><div class="Errors"><ul><li>', t('This page requires Javascript.'), '</li></ul></div></noscript>';
echo $this->Form->open();
echo heading($this->data('Title'));
?>
<div class="toolbar flex-wrap">
    <div class="toolbar-buttons">
        <?php
        echo anchor(t('Approve'), '#', ['class' => 'RestoreButton btn btn-primary']);
        echo anchor(t('Delete Forever'), '#', ['class' => 'DeleteButton btn btn-primary']);
        ?>
    </div>
    <div class="search toolbar-main"><?php
        if (c('Vanilla.Categories.Use')) {
            echo '<div class="input-wrap input-wrap-multiple">';
            echo $this->Form->categoryDropDown('CategoryID', [
                    'Value' => val('ModerationCategoryID', $this->Data),
                    'IncludeNull' => t('Show all categories')]
            );
            echo anchor(t('Filter'), '#', ['class' => 'FilterButton btn btn-primary']);
            echo '</div>';
        }
        ?></div>
    <?php PagerModule::write(['Sender' => $this, 'Limit' => 10, 'View' => 'pager-dashboard']); ?>
</div>
<?php
echo '<div id="LogTable">';
include dirname(__FILE__).'/table.php';
echo '</div>';
?>
<?php
$this->addDefinition('ExpandText', t('more'));
$this->addDefinition('CollapseText', t('less'));
echo $this->Form->close();
