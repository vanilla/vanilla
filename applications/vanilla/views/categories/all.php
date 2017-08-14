<?php if (!defined('APPLICATION')) exit();
include_once $this->fetchViewLocation('helper_functions', 'categories');
echo '<h1 class="H HomepageTitle">'.$this->data('Title').'</h1>';
if ($description = $this->description()) {
    echo wrap($description, 'div', ['class' => 'P PageDescription']);
}
$this->fireEvent('AfterPageTitle');
$categories = $this->data('CategoryTree');
writeCategoryList($categories, 1);

