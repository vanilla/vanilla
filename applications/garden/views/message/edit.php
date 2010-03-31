<?php if (!defined('APPLICATION')) exit();?>
<h1><?php
   if (is_object($this->Message))
      echo T('Edit Message');
   else
      echo T('Add Message');
?></h1>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<ul>
   <li>
      <?php
         echo $this->Form->Label('Page', 'Location');
         echo $this->Form->DropDown('Location', $this->LocationData);
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Position', 'AssetTarget');
         echo $this->Form->DropDown('AssetTarget', $this->AssetData);
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Message', 'Content');
         echo $this->Form->TextBox('Content', array('MultiLine' => TRUE));
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Appearance', 'CssClass');
         echo $this->Form->Radio('CssClass', '', array('value' => 'Info'));
         echo '<div class="Info" style="display: inline;">'.T('Information').'</div>';
         echo $this->Form->Radio('CssClass', '', array('value' => 'Warning'));
         echo '<div class="Warning" style="display: inline;">'.T('Warning').'</div>';
         echo $this->Form->Radio('CssClass', '', array('value' => 'Box'));
         echo '<div class="Box" style="display: inline;">'.T('Panel Box').'</div>';
         echo $this->Form->Radio('CssClass', '', array('value' => ''));
         echo '<span>'.T('None').'</span>';
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->CheckBox('AllowDismiss', 'Allow users to dismiss this message', array('value' => '1'));
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->CheckBox('Enabled', 'Enable this message', array('value' => '1'));
      ?>
   </li>
</ul>
<?php echo $this->Form->Close('Save'); ?>