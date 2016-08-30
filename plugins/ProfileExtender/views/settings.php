<?php if (!defined('APPLICATION')) exit();

$Fields = $this->data('ExtendedFields');

?>
<div class="header-block">
    <h1>Custom Profile Fields</h1>
    <?php echo wrap(Anchor('Add Field', '/settings/profilefieldaddedit/', 'js-modal btn btn-primary'), 'div', array('class' => 'Wrap')); ?>
</div>
<div class="table-wrap">
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
                <td><?php echo (val('Required', $Field, 0)) ? t('Yes') : t('No'); ?></td>
                <td><?php echo (val('OnRegister', $Field, 0)) ? t('Yes') : t('No'); ?></td>
                <td><?php echo (val('OnProfile', $Field, 1)) ? t('Yes') : t('No'); ?></td>
                <!--<td><?php echo (val('OnDiscussion', $Field, 0)) ? t('Yes') : t('No'); ?></td>-->
                <td>
                    <div class="btn-group">
                    <?php
                    echo anchor(dashboardSymbol('edit'), '/settings/profilefieldaddedit/'.$Name, 'js-modal btn btn-icon', ['aria-label' => t('Edit')]);
                    echo anchor(dashboardSymbol('delete'), '/settings/profilefielddelete/'.$Name, 'js-modal btn btn-icon',
                        ['aria-label' => t('Delete'), 'data-content' => ['cssClass' => 'modal-sm']]);
                    ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
