<?php if (!defined('APPLICATION')) exit();
    if (!function_exists('GetOptions')) {
        include $this->fetchViewLocation('helper_functions', 'categories');
    }
    $userID = Gdn::session()->UserID;
    $categoryID = $this->Category->CategoryID;
?>

<h1 class="H HomepageTitle"><?php echo $this->data('Title'); ?></h1>

<?php
    if ($description = $this->description()) {
        echo wrap($description, 'div', ['class' => 'P PageDescription']);
    }
    $this->fireEvent('AfterPageTitle');

    $categories = $this->data('CategoryTree');
    $this->EventArguments['NumRows'] = count($categories);
?>

<h2 class="sr-only"><?php echo t('Category List'); ?></h2>
<ul class="DataList CategoryList">
<?php
    foreach ($categories as $category) {
        $this->EventArguments['Category'] = &$category;
        $this->fireEvent('BeforeCategoryItem');

        writeListItem($category, 1);
    }
?>
</ul>

<div class="PageControls Bottom">
    <?php PagerModule::write(); ?>
</div>
