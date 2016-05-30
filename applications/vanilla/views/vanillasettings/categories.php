<h1><?php echo t('Manage Categories'); ?></h1>

<?php
writeCategoryBreadcrumbs($this->data('Ancestors', []));
?>

<div class="dd tree tree-categories">
<?php
writeCategoryTree($this->data('Categories', []));
?>
</div>
