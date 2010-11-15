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
      printf(T('%s user(s) found.'), $this->Pager->TotalRecords);
      echo '</p>';
      
   ?>
</div>
<div class="FilterMenu">
   <?php echo Anchor(T('Add User'), 'dashboard/user/add', 'Popup SmallButton'); ?>
</div>
<table id="Users" class="AltColumns">
   <thead>
      <tr>
         <th><?php echo T('Username'); ?></th>
         <th class="Alt"><?php echo T('Email'); ?></th>
         <th><?php echo T('First Visit'); ?></th>
         <th class="Alt"><?php echo T('Last Visit'); ?></th>
         <?php if ($EditUser) { ?>
            <th><?php echo T('Options'); ?></th>
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