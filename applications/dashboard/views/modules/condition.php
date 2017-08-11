<?php if (!defined('APPLICATION')) exit();

function _DN($current, $type) {
    if ($current != $type)
        return ' style="display:none"';
    return '';
}

function writeConditionEdit($condition, $sender) {
    $px = $sender->Prefix;
    $form = new Gdn_Form();

    $type = val(0, $condition, '');
    $field = val(1, $condition, '');
    $expr = val(2, $condition, '');

    echo '<tr>';

    // Type.
    echo '<td>',
    $form->dropDown($px.'Type[]', $sender->Types, ['Value' => $type, 'Class' => 'CondType']),
    '</td>';

    echo '<td>';

    // Permission fields.
    echo '<div class="Cond_permission"'._DN($type, Gdn_Condition::PERMISSION).'>',
    $form->dropDown($px.'PermissionField[]', $sender->Permissions, ['Value' => $type == Gdn_Condition::PERMISSION ? $field : '']),
    '</div>';

    // Role fields.
    echo '<div class="Cond_role"'._DN($type, Gdn_Condition::ROLE).'>',
    $form->dropDown($px.'RoleField[]', $sender->Roles, ['Value' => $type == Gdn_Condition::ROLE ? $field : '']),
    '</div>';

    // Textbox field.
    echo '<div class="Cond_request"'._DN($type, Gdn_Condition::REQUEST).'>',
    $form->textBox($px.'Field[]', ['Value' => $type == Gdn_Condition::REQUEST ? $field : '']);
    '</div>';

    echo '</td>';

    // Expression.
    echo '<td>',
        '<div class="Cond_request"'._DN($type, Gdn_Condition::REQUEST).'>',
    $form->textBox($px.'Expr[]', ['Value' => $type == Gdn_Condition::REQUEST ? $expr : '']),
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
        foreach ($this->conditions() as $Condition) {
            writeConditionEdit($Condition, $this);
        }

        // Write a blank row for a new condition.
        if (count($this->conditions()) == 0) {
            writeConditionEdit(Gdn_Condition::blank(), $this);
        }

        // Write a template for new rows.
        echo '<tfoot style="display:none">';
        writeConditionEdit(Gdn_Condition::blank(), $this);
        echo '</tfoot>';
        ?>
    </table>
    <div style="text-align: right; margin-top: 10px;">
        <a class="AddCondition Button"><?php echo sprintf(t('Add %s'), t('Condition')); ?></a>
    </div>
</div>
