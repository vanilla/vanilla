<?php if (!defined('APPLICATION')) exit();
require_once $this->FetchViewLocation('helper_functions');

if (!isset($this->Prefix))
   $this->Prefix = 'Promoted';

?>
<div class="Box BoxPromoted">
   <h4><?php echo T('Promoted Content'); ?></h4>
   <ul class="PanelInfo DataList">
      <?php
      foreach ($this->Data('Content') as $Content):
         WritePromotedContent($Content, $this);
      endforeach;
      ?>
   </ul>
</div>