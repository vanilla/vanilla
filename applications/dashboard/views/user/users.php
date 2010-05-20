<?php if (!defined('APPLICATION')) exit();
$Alt = FALSE;
$Session = Gdn::Session();
$EditUser = $Session->CheckPermission('Garden.Users.Edit');
$DeleteUser = $Session->CheckPermission('Garden.Users.Delete');
foreach ($this->UserData->Format('Text')->Result() as $User) {
   $Alt = $Alt ? FALSE : TRUE;
   ?>
   <tr<?php echo $Alt ? ' class="Alt"' : ''; ?>>
      <td><strong><?php echo UserAnchor($User); ?></strong></td>
      <td class="Alt"><?php echo Gdn_Format::Email($User->Email); ?></td>
      <td><?php echo Gdn_Format::Date($User->DateFirstVisit); ?></td>
      <td class="Alt"><?php echo Gdn_Format::Date($User->DateLastActive); ?></td>
      <?php if ($EditUser || $DeleteUser) { ?>
         <td><?php
         if ($EditUser)
            echo Anchor('Edit', '/user/edit/'.$User->UserID, 'Popup');
            
         if ($EditUser && $DeleteUser)
            echo ' | ';

         if ($DeleteUser)
            echo Anchor('Delete', '/user/delete/'.$User->UserID);
         ?></td>
      <?php } ?>
   </tr>
<?php
}