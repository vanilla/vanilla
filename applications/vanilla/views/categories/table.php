<?php if (!defined('APPLICATION')) return; ?>
<h1 class="HomepageTitle"><?php echo $this->Data('Title'); ?></h1>
<div class="P PageDescription"><?php echo $this->Description(); ?></div>
<?php
$Categories = CategoryModel::MakeTree($this->Data('Categories'));

//decho($Categories);
//die();

if (C('Vanilla.Categories.DoHeadings')) {
   foreach ($Categories as $Category) {
      ?>
      <div id="CategoryGroup-<?php echo $Category['UrlCode']; ?>" class="CategoryGroup">
         <h2><?php echo $Category['Name']; ?></h2>
         <div class="DataTableWrap">
            <?php
            WriteCategoryTable($Category['Children'], 2);
            ?>
         </div>
      </div>
      <?php
   }
} else {
   WriteCategoryTable($Categories, 1);
}
?>