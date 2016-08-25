<?php if (!defined('APPLICATION')) exit(); ?>

<?php if ($this->data('Layout') === 'modern'): ?>
<ul class="DataList CategoryList<?php echo $this->data('DoHeadings') ? ' CategoryListWithHeadings' : ''; ?>">
    <?php
    foreach ($this->data('Categories') as $category) {
        writeListItem($category);
    }
    ?>
</ul>
<?php elseif ($this->data('DoHeadings')): ?>
    <?php foreach ($this->data('Categories', []) as $category): ?>
        <div id="CategoryGroup-<?php echo $category['UrlCode']; ?>" class="CategoryGroup <?php echo val('CssClass', $category); ?>">
            <h2 class="H"><?php echo htmlspecialchars($category['Name']); ?></h2>
            <?php writeCategoryTable($category['Children'], 2); ?>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <?php writeCategoryTable($this->data('Categories', []), 1); ?>
<?php endif; ?>

<div><?php echo wrap(
    anchor(htmlspecialchars(t('View All')), $this->data('ParentCategory.Url')),
    'span',
    ['class' => 'MItem Category']
); ?></div>
