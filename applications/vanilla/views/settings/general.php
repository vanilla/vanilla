<?php if (!defined('APPLICATION')) exit();
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<h1><?php echo Gdn::Translate('Forum Settings'); ?></h1>
<ul>
   <li>
      <?php
         $Options = array('10' => '10', '20' => '20', '30' => '30', '50' => '50', '100' => '100');
         $Fields = array('TextField' => 'Code', 'ValueField' => 'Code');
         echo $this->Form->Label('Discussions per Page', 'Vanilla.Discussions.PerPage');
         echo $this->Form->DropDown('Vanilla.Discussions.PerPage', $Options, $Fields);
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Comments per Page', 'Vanilla.Comments.PerPage');
         echo $this->Form->DropDown('Vanilla.Comments.PerPage', $Options, $Fields);
      ?>
   </li>
   <li>
      <?php
         $Options2 = array('0' => 'Don\'t Refresh', '5' => 'Every 5 seconds', '10' => 'Every 10 seconds', '30' => 'Every 30 seconds', '60' => 'Every 1 minute', '300' => 'Every 5 minutes');
         echo $this->Form->Label('Refresh Comments', 'Vanilla.Comments.AutoRefresh');
         echo $this->Form->DropDown('Vanilla.Comments.AutoRefresh', $Options2, $Fields);
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->CheckBox('Vanilla.Categories.Use', 'Use categories to organize discussions');
      ?>
   </li>
</ul>
<?php echo $this->Form->Close('Save');
