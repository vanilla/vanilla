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
   <h4><?php echo T('Categories'); ?></h4>
   <ul class="PanelInfo PanelCategories">
   <?php
   echo '<li'.($OnCategories ? ' class="Active"' : '').'>'.Wrap(Anchor(Gdn_Format::Text(T('All Categories')), '/categories'), 'strong')
      .' <span class="Aside"><span class="Count">'.BigPlural($CountDiscussions, '%s discussion').'</span></span></li>';

   $MaxDepth = C('Vanilla.Categories.MaxDisplayDepth');
   $DoHeadings = C('Vanilla.Categories.DoHeadings');
   
   foreach ($this->Data->Result() as $Category) {
      if ($Category->CategoryID < 0 || $MaxDepth > 0 && $Category->Depth > $MaxDepth)
         continue;

      if ($DoHeadings && $Category->Depth == 1)
         $CssClass = 'Heading';
      else
         $CssClass = 'Depth'.$Category->Depth.($CategoryID == $Category->CategoryID ? ' Active' : '');
      
      echo '<li class="ClearFix '.$CssClass.'">';

      if ($DoHeadings && $Category->Depth == 1) {
         echo Gdn_Format::Text($Category->Name);
      } else {
         echo Wrap(Anchor(Gdn_Format::Text($Category->Name), CategoryUrl($Category)), 'strong')
            .' <span class="Aside"><span class="Count">'.BigPlural($Category->CountAllDiscussions, '%s discussion').'</span></span>';
      }
      echo "</li>\n";
   }
?>
   </ul>
</div>
   <?php
}