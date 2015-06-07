<?php if (!defined('APPLICATION')) return; ?>
    <h1 class="H HomepageTitle"><?php echo $this->data('Title'); ?></h1>
    <div class="P PageDescription"><?php echo $this->Description(); ?></div>
<?php
$this->fireEvent('AfterDescription');
$this->fireEvent('AfterPageTitle');
$Categories = CategoryModel::MakeTree($this->data('Categories'), $this->data('Category', null));

if (c('Vanilla.Categories.DoHeadings')) {
    foreach ($Categories as $Category) {
        ?>
        <div id="CategoryGroup-<?php echo $Category['UrlCode']; ?>"
             class="CategoryGroup <?php echo val('CssClass', $Category); ?>">
            <h2 class="H"><?php echo htmlspecialchars($Category['Name']); ?></h2>
            <?php
            WriteCategoryTable($Category['Children'], 2);
            ?>
        </div>
    <?php
    }
} else {
    WriteCategoryTable($Categories, 1);
}
?>
