<?php if (!defined('APPLICATION')) exit(); ?>
<?php $parentCategories = $this->data('ParentCategories'); ?>
<h1><?php echo t('Move Category'); ?></h1>
<div class="Info">
    <?php echo sprintf(
        t('Move %s to one of its parent categories.'),
        val('Name', $this->data('Category'))
    ); ?>
</div>
<?php if (empty($parentCategories)): ?>
<div class="Warning"><?php echo t('No parent categories available.'); ?></div>
<?php else: ?>
<?php echo $this->Form->open(); ?>
<ul>
    <li>
        <?php
            echo $this->Form->label('Parent Category', 'ParentCategoryID');
            echo $this->Form->dropdown(
                'ParentCategoryID', $parentCategories
            );
        ?>
    </li>
</ul>
<?php echo $this->Form->close('Save'); ?>
<?php endif; ?>
