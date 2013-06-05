<?php if (!defined('APPLICATION')) exit();

$Fields = $this->Data('ExtendedFields');

?>

<h1>Custom Profile Fields</h1>
<?php echo Wrap(Anchor('Add Field', '/settings/profilefieldaddedit/', 'Popup SmallButton'), 'div', array('class' => 'Wrap')); ?>
<table>
   <thead>
      <tr>
         <th>Label</th>
         <th>Type</th>
         <th>Required</th>
         <th>On Registration</th>
         <th>In Profiles</th>
         <!--<th>In Discussions</th>-->
         <th>Options</th>
      </tr>
   </thead>
   <tbody>

<?php foreach ($Fields as $Name => $Field) : ?>
      <tr>
         <td><?php echo $Field['Label']; ?></td>
         <td><?php echo $Field['FormType']; ?></td>
         <td><?php echo (GetValue('Required', $Field, 0)) ? T('Yes') : T('No'); ?></td>
         <td><?php echo (GetValue('OnRegister', $Field, 0)) ? T('Yes') : T('No'); ?></td>
         <td><?php echo (GetValue('OnProfile', $Field, 1)) ? T('Yes') : T('No'); ?></td>
         <!--<td><?php echo (GetValue('OnDiscussion', $Field, 0)) ? T('Yes') : T('No'); ?></td>-->
         <td><?php echo Anchor('Edit', '/settings/profilefieldaddedit/'.$Name, 'Popup SmallButton') .
            ' ' . Anchor('Delete', '/settings/profilefielddelete/'.$Name, 'Popup SmallButton'); ?></td>
      </tr>
<?php endforeach; ?>
   </tbody>
</table>
