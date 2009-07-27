<?php if (!defined('APPLICATION')) exit();

echo $this->Form->Open();
echo $this->Form->Errors();
?>
<h1><?php
   if (is_object($this->Message))
      echo Gdn::Translate('Edit Message');
   else
      echo Gdn::Translate('Add Message');
?></h1>
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