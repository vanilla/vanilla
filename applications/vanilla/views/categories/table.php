<?php if (!defined('APPLICATION')) return; ?>
<h1 class="H HomepageTitle"><?php echo $this->Data('Title'); ?></h1>
<div class="P PageDescription"><?php echo $this->Description(); ?></div>
<?php
$Categories = CategoryModel::MakeTree($this->Data('Categories'), $this->Data('Category', NULL));

if (C('Vanilla.Categories.DoHeadings')) {
   foreach ($Categories as $Category) {
      ?>
      <div id="CategoryGroup-<?php echo $Category['UrlCode']; ?>" class="CategoryGroup">
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