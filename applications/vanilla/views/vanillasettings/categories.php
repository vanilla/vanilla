<div class="header-block">
    <div class="header-title">
        <h1><?php echo t('Manage Categories') ?></h1>
    </div>
    <div class="header-buttons">
        <?php
        echo anchor(
            t('Add Category'),
            'vanilla/settings/addcategory?parent='.$this->data('Category.CategoryID'),
            'btn btn-primary'
        );
        ?>
    </div>
</div>

<?php
    writeCategoryBreadcrumbs($this->data('Ancestors', []));
?>

<div class="toolbar"><?php if ($this->data('UsePagination', false) === true) {
    PagerModule::write(['Sender' => $this, 'View' => 'pager-dashboard']);
} ?></div>

<div class="dd tree tree-categories"><?php
    writeCategoryTree($this->data('Categories', []), 0, $this->data('AllowSorting', true));
?></div>
