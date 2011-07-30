<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$EditUser = $Session->CheckPermission('Garden.Users.Edit');
echo $this->Form->Open(array('action' => Url('/user/browse')));
?>
<h1><?php echo T('Manage Users'); ?></h1>
<div class="Info">
   <?php
      echo $this->Form->Errors();

      echo '<div>', T('Search by user or role.', 'Search for users by name or enter the name of a role to see all users with that role.'), '</div>';

      echo '<p>';
      echo $this->Form->TextBox('Keywords');
      echo $this->Form->Button(T('Go'));
      printf(T('%s user(s) found.'), $this->Data('RecordCount'));
      echo '</p>';
      
   ?>
</div>
<div class="FilterMenu">
   <?php echo Anchor(T('Add User'), 'dashboard/user/add', 'Popup SmallButton'); ?>
</div>
<table id="Users" class="AltColumns">
   <thead>
      <tr>
         <th><?php echo Anchor(T('Username'), $this->_OrderUrl('Name')); ?></th>
         <th class="Alt"><?php echo T('Email'); ?></th>
         <th><?php echo T('Roles'); ?></th>
         <th class="Alt"><?php echo Anchor(T('First Visit'), $this->_OrderUrl('DateFirstVisit')); ?></th>
         <th><?php echo Anchor(T('Last Visit'), $this->_OrderUrl('DateLastActive')); ?></th>
         <th><?php echo T('Last IP'); ?></th>
         <?php
         $this->FireEvent('UserCell');
         ?>
         <?php if ($EditUser) { ?>
            <th><?php echo T('Options'); ?></th>
         <?php } ?>
      </tr>
   </thead>
   <tbody>
      <?php
      include($this->FetchViewLocation('users'));
      ?>
   </tbody>
</table>
<?php
PagerModule::Write(array('Sender' => $this));
echo $this->Form->Close();