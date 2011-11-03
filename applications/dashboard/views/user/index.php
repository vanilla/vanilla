<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$EditUser = $Session->CheckPermission('Garden.Users.Edit');
?>
<div class="Help Aside">
   <?php
   echo Wrap(T('Need More Help?'), 'h2');
   echo '<ul>';
   echo Wrap(Anchor(T("Video tutorial on finding &amp; managing users"), 'settings/tutorials/users'), 'li');
   echo '</ul>';
   ?>
</div>
<h1><?php echo T('Manage Users'); ?></h1>
<?php echo $this->Form->Open(array('action' => Url('/user/browse'))); ?>
<div class="Wrap">
   <?php
      echo $this->Form->Errors();

      echo '<div>', T('Search by user or role.', 'Search for users by name or enter the name of a role to see all users with that role.'), '</div>';

      echo '<div>';
      echo $this->Form->TextBox('Keywords');
      echo ' ', $this->Form->Button(T('Go'));
      echo ' ', sprintf(T('%s user(s) found.'), $this->Data('RecordCount'));
      echo '</div>';
      
   ?>
</div>
<div class="Wrap">
<!--   <span class="ButtonList">
      <?php
         echo Anchor(T('Ban'), '#', 'Popup SmallButton');
         echo Anchor(T('Unban'), '#', 'Popup SmallButton');
         echo Anchor(T('Delete'), '#', 'Popup SmallButton');
      ?>
   </span>-->
   
   <?php echo Anchor(T('Add User'), 'dashboard/user/add', 'Popup SmallButton'); ?>
</div>
<table id="Users" class="AltColumns">
   <thead>
      <tr>
<!--         <th class="CheckboxCell"><input id="SelectAll" type="checkbox" /></th>-->
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