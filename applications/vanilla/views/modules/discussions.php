<?php if (!defined('APPLICATION')) exit();
require_once $this->FetchViewLocation('helper_functions');

if (!isset($this->Prefix))
   $this->Prefix = 'Discussion';
?>
<div class="Box BoxDiscussions">
   <h4><?php echo T('Recent Discussions'); ?></h4>
   <ul class="PanelInfo PanelDiscussions DataList">
      <?php
      foreach ($this->Data('Discussions')->Result() as $Discussion) {
         WriteModuleDiscussion($Discussion, $this->Prefix);
      }
      if ($this->Data('Discussions')->NumRows() >= $this->Limit) {
      ?>
      <li class="ShowAll"><?php echo Anchor(T('More…'), 'discussions'); ?></li>
      <?php } ?>
   </ul>
</div>