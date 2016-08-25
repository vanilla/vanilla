<?php if (!defined('APPLICATION')) exit(); ?>

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

<div><?php echo wrap(
    anchor(htmlspecialchars(t('View All')), $this->data('ParentCategory.Url')),
    'span',
    ['class' => 'MItem Category']
); ?></div>
