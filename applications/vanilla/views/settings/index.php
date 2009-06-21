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
         echo $this->Form->CheckBox('Vanilla.Categories.Use', 'Use categories to organize discussions');
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Forum Home Screen', 'Vanilla.Home');
         echo $this->Form->RadioList(
            'Vanilla.Discussions.Home',
            array(
               'discussions' => Gdn::Translate('Discussions'),
               'categories' => Gdn::Translate('Categories')
            ),
            array('TextField' => 'Code', 'ValueField' => 'Code'));
      ?>
   </li>   
</ul>
<?php echo $this->Form->Close('Save');
