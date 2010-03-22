<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$EditUser = $Session->CheckPermission('Garden.Users.Edit');
echo $this->Form->Open(array('action' => Url('/user/browse')));
?>
<h1><?php echo Gdn::Translate('Manage Users'); ?></h1>
<div class="FilterMenu">
   <?php echo Anchor(Gdn::Translate('Add User'), 'garden/user/add', 'Popup Button'); ?>
</div>
<div class="Info">
   <?php
      echo $this->Form->Errors();
      echo $this->Form->TextBox('Keywords');
      echo $this->Form->Button(Gdn::Translate('Go'));
      printf(Gdn::Translate('%s user(s) found.'), $this->Pager->TotalRecords);
      
   ?>
</div>
<table id="Users" class="AltColumns">
   <thead>
      <tr>
         <th><?php echo Gdn::Translate('Username'); ?></th>
         <th class="Alt"><?php echo Gdn::Translate('Email'); ?></th>
         <th><?php echo Gdn::Translate('First Visit'); ?></th>
         <th class="Alt"><?php echo Gdn::Translate('Last Visit'); ?></th>
         <?php if ($EditUser) { ?>
            <th><?php echo Gdn::Translate('Options'); ?></th>
         <?php } ?>
      </tr>
   </thead>
   <tbody>
      <?php
      echo $this->Pager->ToString('less');
      include($this->FetchViewLocation('users'));
      echo $this->Pager->ToString('more');
      ?>
   </tbody>
</table>
<?php
echo $this->Form->Close();