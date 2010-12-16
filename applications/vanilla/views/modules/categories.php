<?php if (!defined('APPLICATION')) exit();
$CountDiscussions = 0;
$CategoryID = isset($this->_Sender->CategoryID) ? $this->_Sender->CategoryID : '';

if ($this->Data !== FALSE) {
   foreach ($this->Data->Result() as $Category) {
      $CountDiscussions = $CountDiscussions + $Category->CountDiscussions;
   }
   ?>
<div class="Box BoxCategories">
   <h4><?php echo Anchor(T('Categories'), 'categories/all'); ?></h4>
   <ul class="PanelInfo PanelCategories">
      <li<?php
      if (!is_numeric($CategoryID))
         echo ' class="Active"';
         
      ?>><span><strong><?php echo Anchor(Gdn_Format::Text(T('All Discussions')), '/discussions'); ?></strong> <?php echo $CountDiscussions; ?></span></li>
<?php
   foreach ($this->Data->Result() as $Category) {
      if ($Category->CategoryID > 0) {
         // Only check stack if there is one
         echo '<li class="Depth'.$Category->Depth.($CategoryID == $Category->CategoryID ? ' Active' : '').'">';
         echo Wrap(Anchor(($Category->Depth > 1 ? '↳ ' : '').Gdn_Format::Text($Category->Name), '/categories/'.$Category->UrlCode), 'strong');
         echo ' '.$Category->CountDiscussions;
         echo "</li>\n";
      }
   }
?>
   </ul>
</div>
   <?php
}