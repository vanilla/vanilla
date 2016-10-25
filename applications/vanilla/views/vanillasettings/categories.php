<?php if (!defined('APPLICATION')) exit();
echo heading(t('Manage Categories'), t('Add Category'), 'vanilla/settings/addcategory?parent='.$this->data('Category.CategoryID'));
writeCategoryBreadcrumbs($this->data('Ancestors', []));
?>

<div class="toolbar"><?php if ($this->data('UsePagination', false) === true) {
    PagerModule::write(['Sender' => $this, 'View' => 'pager-dashboard']);
} ?></div>

<div class="dd tree tree-categories" data-parent-id="<?php echo $this->data('ParentID', -1); ?>"><?php
    writeCategoryTree($this->data('Categories', []), 0, $this->data('AllowSorting', true));
?></div>
