<?php if (!defined('APPLICATION')) exit();
require_once $this->FetchViewLocation('helper_functions');

if (!isset($this->Prefix))
   $this->Prefix = 'Discussion';
?>
<div class="Box BoxDiscussions">
   <?php echo panelHeading(T('Recent Discussions')); ?>
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