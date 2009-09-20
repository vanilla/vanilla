<?php if (!defined('APPLICATION')) exit();
$Alt = FALSE;
if (!isset($EditUser)) {
   $Session = Gdn::Session();
   $EditUser = $Session->CheckPermission('Garden.Users.Edit');
}
   
foreach ($this->UserData->Result('Text') as $User) {
   $Alt = $Alt ? FALSE : TRUE;
   ?>
   <tr<?php echo $Alt ? ' class="Alt"' : ''; ?>>
      <td><?php echo UserAnchor($User); ?></td>
      <td class="Alt"><?php echo Format::Email($User->Email); ?></td>
      <td><?php echo Format::Date($User->DateFirstVisit); ?></td>
      <td class="Alt"><?php echo Format::Date($User->DateLastActive); ?></td>
      <?php if ($EditUser) { ?>
         <td><?php echo Anchor('Edit', '/user/edit/'.$User->UserID, 'Popup'); ?></td>
      <?php } ?>
   </tr>
<?php
}