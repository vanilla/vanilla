<div class="header-block">
    <div class="header-title">
        <h1><?php echo t('Manage Categories') ?></h1>
    </div>
    <div class="header-buttons">
        <?php
        echo anchor(t('Add Category'), 'vanilla/settings/addcategory', 'btn btn-primary');
        ?>
    </div>
</div>

<?php
writeCategoryBreadcrumbs($this->data('Ancestors', []));
?>

<?php
writeCategoryBreadcrumbs($this->data('Ancestors', []));
?>

<div class="dd tree tree-categories">
<?php
writeCategoryTree($this->data('Categories', []));
?>
</div>
