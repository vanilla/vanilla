<?php if (!defined('APPLICATION')) exit();

function _DN($Current, $Type) {
    if ($Current != $Type)
        return ' style="display:none"';
    return '';
}

function writeConditionEdit($Condition, $Sender) {
    $Px = $Sender->Prefix;
    $Form = new Gdn_Form();

    $Type = val(0, $Condition, '');
    $Field = val(1, $Condition, '');
    $Expr = val(2, $Condition, '');

    echo '<tr>';

    // Type.
    echo '<td>',
    $Form->DropDown($Px.'Type[]', $Sender->Types, array('Value' => $Type, 'Class' => 'CondType')),
    '</td>';

    echo '<td>';

    // Permission fields.
    echo '<div class="Cond_permission"'._DN($Type, Gdn_Condition::PERMISSION).'>',
    $Form->DropDown($Px.'PermissionField[]', $Sender->Permissions, array('Value' => $Type == Gdn_Condition::PERMISSION ? $Field : '')),
    '</div>';

    // Role fields.
    echo '<div class="Cond_role"'._DN($Type, Gdn_Condition::ROLE).'>',
    $Form->DropDown($Px.'RoleField[]', $Sender->Roles, array('Value' => $Type == Gdn_Condition::ROLE ? $Field : '')),
    '</div>';

    // Textbox field.
    echo '<div class="Cond_request"'._DN($Type, Gdn_Condition::REQUEST).'>',
    $Form->textBox($Px.'Field[]', array('Value' => $Type == Gdn_Condition::REQUEST ? $Field : ''));
    '</div>';

    echo '</td>';

    // Expression.
    echo '<td>',
        '<div class="Cond_request"'._DN($Type, Gdn_Condition::REQUEST).'>',
    $Form->textBox($Px.'Expr[]', array('Value' => $Type == Gdn_Condition::REQUEST ? $Expr : '')),
    '</div>',
    '</td>';

    // Buttons.
    echo '<td align="right">',
    '<a href="#" class="DeleteCondition">',
    t('Delete'),
    '</a></td>';

    echo '</tr>';
}

?>
<div class="ConditionEdit">
    <table class="ConditionEdit">
        <thead>
        <tr>
            <th width="30%"><?php echo t('Condition Type', 'Type'); ?></th>
            <th width="30%"><?php echo t('Condition Field', 'Field'); ?></th>
            <th width="30%"><?php echo t('Condition Expression', 'Value'); ?></th>
            <th>&#160;</th>
        </tr>
        </thead>
        <?php
        // Write all of the conditions.
        foreach ($this->Conditions() as $Condition) {
            WriteConditionEdit($Condition, $this);
        }

        // Write a blank row for a new condition.
        if (count($this->Conditions()) == 0) {
            WriteConditionEdit(Gdn_Condition::Blank(), $this);
        }

        // Write a template for new rows.
        echo '<tfoot style="display:none">';
        WriteConditionEdit(Gdn_Condition::Blank(), $this);
        echo '</tfoot>';
        ?>
    </table>
    <div style="text-align: right; margin-top: 10px;">
        <a class="AddCondition Button"><?php echo sprintf(t('Add %s'), t('Condition')); ?></a>
    </div>
</div>
