<?php if (!defined('APPLICATION')) exit();
$CountDiscussions = 0;
$CategoryID = isset($this->_Sender->CategoryID) ? $this->_Sender->CategoryID : '';
$OnCategories = strtolower($this->_Sender->ControllerName) == 'categoriescontroller' && !is_numeric($CategoryID);
if ($this->Data !== FALSE) {
   foreach ($this->Data->Result() as $Category) {
      $CountDiscussions = $CountDiscussions + $Category->CountDiscussions;
   }
   ?>
<div class="Box BoxCategories">
   <?php echo panelHeading(T('Categories')); ?>
   <ul class="PanelInfo PanelCategories">
   <?php
   echo '<li'.($OnCategories ? ' class="Active"' : '').'>'.
      Anchor('<span class="Aside"><span class="Count">'.BigPlural($CountDiscussions, '%s discussion').'</span></span> '.T('All Categories'), '/categories', 'ItemLink')
      .'</li>';

   $MaxDepth = C('Vanilla.Categories.MaxDisplayDepth');

   foreach ($this->Data->Result() as $Category) {
      if ($Category->CategoryID < 0 || $MaxDepth > 0 && $Category->Depth > $MaxDepth)
         continue;

      if ($Category->DisplayAs === 'Heading')
         $CssClass = 'Heading '.$Category->CssClass;
      else
         $CssClass = 'Depth'.$Category->Depth.($CategoryID == $Category->CategoryID ? ' Active' : '').' '.$Category->CssClass;

      echo '<li class="ClearFix '.$CssClass.'">';

      $CountText = '<span class="Aside"><span class="Count">'.BigPlural($Category->CountAllDiscussions, '%s discussion').'</span></span>';

      if ($Category->DisplayAs === 'Heading') {
         echo $CountText.' '.htmlspecialchars($Category->Name);
      } else {
         echo Anchor($CountText.' '.htmlspecialchars($Category->Name), CategoryUrl($Category), 'ItemLink');
      }
      echo "</li>\n";
   }
?>
   </ul>
</div>
   <?php
}
