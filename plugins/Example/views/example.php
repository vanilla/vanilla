<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo T($this->Data['Title']); ?></h1>
<div class="Info">
   <?php echo T($this->Data['PluginDescription']); ?>
</div>
<h3><?php echo T('Settings'); ?></h3>
<?php
   echo $this->Form->Open();
   echo $this->Form->Errors();
?>
<ul>
   <li><?php
      echo $this->Form->Label('Display condition', 'Plugin.Example.RenderCondition');
      echo $this->Form->DropDown('Plugin.Example.RenderCondition',array(
         'all'             => 'Discussions & Announcements',
         'announcements'   => 'Just Announcements',
         'discussions'     => 'Just Discussions'
      ));
   ?></li>
   <li><?php
      echo $this->Form->Label('Excerpt length', 'Plugin.Example.TrimSize');
      echo $this->Form->Textbox('Plugin.Example.TrimSize');
   ?></li>
</ul>
<?php
   echo $this->Form->Close('Save');
?>