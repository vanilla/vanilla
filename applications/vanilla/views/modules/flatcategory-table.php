<?php if (!defined('APPLICATION')) return; ?>

<?php if ($this->data('DoHeadings')): ?>
    <?php foreach ($this->data('Categories', []) as $category): ?>
    <div id="CategoryGroup-<?php echo $category['UrlCode']; ?>" class="CategoryGroup <?php echo val('CssClass', $category); ?>">
        <h2 class="H"><?php echo htmlspecialchars($category['Name']); ?></h2>
        <?php writeCategoryTable($category['Children'], 2); ?>
    </div>
    <?php endforeach; ?>
<?php else: ?>
    <?php writeCategoryTable($this->data('Categories', []), 1); ?>
<?php endif; ?>

<?php echo wrap(
    anchor(htmlspecialchars(t('View All')), $this->data('ParentCategory.Url')),
    'span',
    ['class' => 'MItem Category']
); ?>
