<?php if (!defined('APPLICATION')) exit();

function WriteConditionEdit($Condition, $Sender) {
   $Px = $Sender->Prefix;
   $Form = new Gdn_Form();

   $Type = GetValue(0, $Condition, '');
   $Field = GetValue(1, $Condition, '');
   $Expr = GetValue(2, $Condition, '');

   echo '<tr>';
   
   // Type.
   echo '<td>',
      $Form->DropDown($Px.'Type[]', $Sender->Types, array('Value' => $Type)),
      '</td>';

   echo '<td>';

   // Permission fields.
   echo '<div class="Cond_permission">',
      $Form->DropDown($Px.'PermissionField[]', $Sender->Permissions),
      '</div>';

   // Role fields.
   echo '<div class="cond_role">',
      $Form->DropDown($Px.'RoleField[]', $Sender->Roles, array('Value' => $Condition)),
      '</div>';

   // Textbox field.
   echo '<div class="ConditionField">',
      $Form->TextBox($Px.'Field[]');
      '</div>';

   echo '</td>';

   // Expression.
   echo '<td>',
      $Form->TextBox($Px.'Expr[]');
      '</td>';

   echo '</tr>';
}
?>
<table class="ConditionEdit">
   <thead>
      <tr>
         <th><?php echo T('Condition Type', 'Type'); ?></th>
         <th><?php echo T('Condition Field', 'Field'); ?></th>
         <th><?php echo T('Condition Expression', 'Value'); ?></th>
      </tr>
   </thead>
   <?php
   // Write all of the conditions.
   foreach ($this->Conditions() as $Condition) {
      WriteConditionEdit($Condition, $this);
   }

   // Write a blank row for a new condition.
   WriteConditionEdit(Gdn_Condition::Blank(), $this);

   // Write a template for new rows.
   echo '<tfoot style="display:none">';
   WriteConditionEdit(Gdn_Condition::Blank(), $this);
   echo '</tfoot>';
   ?>
</table>