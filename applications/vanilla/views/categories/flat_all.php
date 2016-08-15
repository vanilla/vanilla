<?php if (!defined('APPLICATION')) exit();

    if (!function_exists('GetOptions')) {
        include $this->fetchViewLocation('helper_functions', 'categories');
    }
?>

<h1 class="H HomepageTitle"><?php echo $this->data('Title'); ?></h1>

<?php
    if ($description = $this->Description()) {
        echo wrap($description, 'div', array('class' => 'P PageDescription'));
    }
    $this->fireEvent('AfterPageTitle');

    $categories = $this->data('CategoryTree');
    $doHeadings = c('Vanilla.Categories.DoHeadings');
    $this->EventArguments['NumRows'] = count($categories);
?>

<ul class="DataList CategoryList<?php echo $doHeadings ? ' CategoryListWithHeadings' : ''; ?>">
<?php
    foreach ($categories as $category) {
        $this->EventArguments['Category'] = &$category;
        $this->fireEvent('BeforeCategoryItem');

        writeListItem($category);
    }
?>
</ul>

<div class="PageControls Bottom">
    <?php PagerModule::write(); ?>
</div>
