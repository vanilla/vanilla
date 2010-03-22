<?php if (!defined('APPLICATION')) exit(); ?>
<h2><?php echo Gdn::Translate('Edit My Account'); ?></h2>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<ul>
   <li>
      <?php
         echo $this->Form->Label('Username', 'Name');
         echo $this->Form->TextBox('Name');
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Email', 'Email');
         echo $this->Form->TextBox('Email');
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->CheckBox('ShowEmail', Gdn::Translate('Allow other members to see your email?'), array('value' => '1'));
      ?>
   </li>   
   <li class="Gender">
      <?php
         echo $this->Form->Label('Gender', 'Gender');
         echo $this->Form->RadioList('Gender', $this->GenderOptions, array('default' => 'm'))
      ?>
   </li>
   <?php
      $this->FireEvent('EditMyAccountAfter');
   ?>
</ul>
<?php echo $this->Form->Close('Save');