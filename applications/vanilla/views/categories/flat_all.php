<?php if (!defined('APPLICATION')) exit();
    if (!function_exists('GetOptions')) {
        include $this->fetchViewLocation('helper_functions', 'categories');
    }
    $userID = Gdn::session()->UserID;
    $categoryID = $this->Category->CategoryID;
    $tag = headingTag($this);
    echo "<$tag class='H HomepageTitle'>$this->data('Title')</$tag>";
?>

<?php
    if ($description = $this->description()) {
        echo wrap($description, 'div', ['class' => 'P PageDescription']);
    }
    $this->fireEvent('AfterPageTitle');

    $categories = $this->data('CategoryTree');
    $this->EventArguments['NumRows'] = count($categories);
    $subTitleCat = headingTag($this);
    echo "<$subTitleCat class='sr-only'>" . t('Category List') . "</$subTitleCat>";
?>

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
