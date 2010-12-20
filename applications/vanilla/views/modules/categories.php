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
         
      ?>><span><strong><?php echo Anchor(Gdn_Format::Text(T('All Discussions')), '/discussions'); ?></strong> <?php echo number_format($CountDiscussions); ?></span></li>
<?php
   foreach ($this->Data->Result() as $Category) {
      if ($Category->CategoryID > 0) {
         echo '<li class="Depth'.$Category->Depth.($CategoryID == $Category->CategoryID ? ' Active' : '').'">'
            .Wrap(Anchor(($Category->Depth > 1 ? '↳ ' : '').Gdn_Format::Text($Category->Name), '/categories/'.$Category->UrlCode), 'strong')
            .' '.number_format($Category->CountAllDiscussions)
         ."</li>\n";
      }
   }
?>
   </ul>
</div>
   <?php
}