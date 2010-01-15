<?php if (!defined('APPLICATION')) exit(); ?>
<h2><?php echo Gdn::Translate('My Preferences'); ?></h2>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<ul>
   <?php
   foreach ($this->Preferences as $PreferenceGroup => $Preferences) {
      ?>
      <li>
         <?php
            echo $this->Form->Label(Gdn::Translate($PreferenceGroup));
            foreach ($Preferences as $Name => $Description) {
               echo $this->Form->CheckBox($Name, $Description, array('value' => '1'));
            }
         ?>
      </li>
   <?php } ?>
</ul>
<?php echo $this->Form->Close(Gdn::Translate('Save Preferences'));