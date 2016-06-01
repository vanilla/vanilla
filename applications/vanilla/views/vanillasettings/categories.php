<div class="heading-bar">
    <div class="actions">
        <?php
        echo anchor(t('Add Category'), 'vanilla/settings/addcategory', 'Button');
        ?>
    </div>
<h1><?php echo t('Manage Categories'); ?></h1>
</div>

<?php
writeCategoryBreadcrumbs($this->data('Ancestors', []));
?>

<div class="dd tree tree-categories">
<?php
writeCategoryTree($this->data('Categories', []));
?>
</div>
