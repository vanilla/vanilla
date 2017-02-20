<?php if (!defined('APPLICATION')) exit();
echo heading(t('Manage Categories'), t('Add Category'), 'vanilla/settings/addcategory?parent='.$this->data('Category.CategoryID'));
writeCategoryBreadcrumbs($this->data('Ancestors', []));
?>

<div class="toolbar">
    <?php
    /** @var CategoryFilterModule $categoryFilterModule */
    $options['hideContainerSelector'] = '.js-tree-categories';
    echo categoryFilterBox($options);
    if ($this->data('UsePagination', false) === true) {
        PagerModule::write(['Sender' => $this, 'View' => 'pager-dashboard']);
    }
    ?>
</div>
<div class="category-filter-container padded-top"></div>
<div class="dd tree tree-categories js-tree-categories padded-top" data-parent-id="<?php echo $this->data('ParentID', -1); ?>"><?php
    writeCategoryTree($this->data('Categories', []), 0, $this->data('AllowSorting', true));
?></div>
