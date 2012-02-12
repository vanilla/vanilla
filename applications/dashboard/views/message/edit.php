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
         echo $this->Form->DropDown('Location', $this->Data('Locations'));
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
   <li class="MessageExamples">
      <?php
         $Style = ' style="display: inline; padding: 2px 6px 2px 6px; margin: 0 6px 0 0;"';
         echo $this->Form->Label('Appearance', 'CssClass');
         echo $this->Form->Radio('CssClass', '', array('value' => 'CasualMessage'));
         echo '<div class="CasualMessage"'.$Style.'>'.T('Casual').'</div>';
         echo $this->Form->Radio('CssClass', '', array('value' => 'InfoMessage'));
         echo '<div class="InfoMessage"'.$Style.'>'.T('Information').'</div>';
         echo $this->Form->Radio('CssClass', '', array('value' => 'AlertMessage'));
         echo '<div class="AlertMessage"'.$Style.'>'.T('Alert').'</div>';
         echo $this->Form->Radio('CssClass', '', array('value' => 'WarningMessage'));
         echo '<div class="WarningMessage"'.$Style.'>'.T('Warning').'</div>';
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
<?php echo $this->Form->Close('Save');