<?php if (!defined('APPLICATION')) exit();
$CountDiscussions = 0;
$CategoryID = isset($this->_Sender->CategoryID) ? $this->_Sender->CategoryID : '';

if ($this->Data !== FALSE) {
   foreach ($this->Data->Result() as $Category) {
      $CountDiscussions = $CountDiscussions + $Category->CountDiscussions;
   }
   ?>
<div class="Box BoxCategories">
   <h4><?php echo Anchor(T('Categories'), 'categories'); ?></h4>
   <ul class="PanelInfo PanelCategories">
      <li class="ClearFix<?php
      /*
       if (!is_numeric($CategoryID))
         echo ' Active';?>"><span><strong><?php echo Anchor(Gdn_Format::Text(T('All Discussions')), '/discussions'); ?></strong> <span class="Aside"><span class="Count"><?php echo Gdn_Format::BigNumber($CountDiscussions, 'html'); ?></span></span></span></li>
<?php
      */
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
         echo Wrap(Anchor(Gdn_Format::Text($Category->Name), '/categories/'.rawurlencode($Category->UrlCode)), 'strong')
            .' <span class="Aside"><span class="Count">'.number_format($Category->CountAllDiscussions).'</span></span>';
      }
      echo "</li>\n";
   }
?>
   </ul>
</div>
   <?php
}