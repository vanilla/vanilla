<?php if (!defined('APPLICATION')) exit(); ?>

<div class="Box BoxFlatCategory">
    <h2><?php echo htmlspecialchars($this->data('ParentCategory.Name')); ?></h2>

    <?php if ($this->data('Layout') === 'table'): ?>
        <?php writeCategoryTable($this->data('Categories', []), 1); ?>
    <?php else: ?>
    <ul class="DataList CategoryList">
        <?php
        foreach ($this->data('Categories') as $category) {
            writeListItem($category);
        }
        ?>
    </ul>
    <?php endif; ?>

    <div class="MoreWrap"><?php echo wrap(
        anchor(htmlspecialchars(t('View All')), $this->data('ParentCategory.Url')),
        'span',
        ['class' => 'MItem Category']
    ); ?></div>
</div>
