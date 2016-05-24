<h1><?php echo t('Manage Categories'); ?></h1>

<div class="dd tree tree-categories">
<?php
writeCategoryTree($this->data('Categories', []));
?>
</div>
