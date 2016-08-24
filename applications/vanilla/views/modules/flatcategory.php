<?php if (!defined('APPLICATION')) return; ?>

<?php if (c('Vanilla.Categories.DoHeadings')): ?>
    <?php foreach ($this->data('Categories', []) as $category): ?>
    <div id="CategoryGroup-<?php echo $category['UrlCode']; ?>" class="CategoryGroup <?php echo val('CssClass', $category); ?>">
        <h2 class="H"><?php echo htmlspecialchars($category['Name']); ?></h2>
        <?php writeCategoryTable($category['Children'], 2); ?>
    </div>
    <?php endforeach; ?>
<?php else: ?>
    <?php writeCategoryTable($this->data('Categories', []), 1); ?>
<?php endif; ?>
