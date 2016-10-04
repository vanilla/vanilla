<?php if (!defined('APPLICATION')) exit();
if (isset($this->CategoryModel) && $this->CategoryModel instanceof CategoryModel) {
    $childCategories = $this->data('CategoryTree', []);
    $this->CategoryModel->joinRecent($childCategories);
    if ($childCategories) {
        include($this->fetchViewLocation('helper_functions', 'categories', 'vanilla'));
        if (c('Vanilla.Categories.Layout') === 'table') {
            writeCategoryTable($childCategories);
        } else {
            writeCategoryList($childCategories);
        }
    }
}
