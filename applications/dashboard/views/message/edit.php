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
         $Style = ' style="display: inline; padding: 2px 6px 2px 6px; margin: 0 6px 0 0;"';
         echo $this->Form->Label('Appearance', 'CssClass');
         echo $this->Form->Radio('CssClass', '', array('value' => 'Info'));
         echo '<div class="Info"'.$Style.'>'.T('Information').'</div>';
         echo $this->Form->Radio('CssClass', '', array('value' => 'Warning'));
         echo '<div class="Warning"'.$Style.'>'.T('Warning').'</div>';
         echo $this->Form->Radio('CssClass', '', array('value' => 'Box'));
         echo '<div class="Box"'.$Style.'>'.T('Panel Box').'</div>';
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