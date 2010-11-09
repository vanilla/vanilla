<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo T($this->Data['Title'].' Settings'); ?></h1>
<div class="Info">
   <?php echo T($this->Data['Description']); ?>
</div>
<div class="FilterMenu">
      <?php
      $FormAction = $this->Plugin->AutoTogglePath();
      echo $this->Form->Open(array(
         'action'    => Url($FormAction),
         'jsaction'  => $FormAction
      ));
      echo $this->Form->Errors();
      
      $PluginName = $this->Plugin->GetPluginKey('Name');
      $ButtonName = T($this->Plugin->IsEnabled() ? "Disable {$PluginName}" : "Enable {$PluginName}");
      
      echo $this->Form->Close($ButtonName, '', array(
                              'class' => 'SliceSubmit SliceForm SmallButton'
                           ));
   ?>
</div>