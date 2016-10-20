<?php if (!defined('APPLICATION')) exit();
$Fields = $this->data('ExtendedFields');
echo heading(t('Custom Profile Fields'), t('Add Field'), '/settings/profilefieldaddedit/', 'js-modal btn btn-primary')
?>
<div class="table-wrap">
    <table class="table-data js-tj">
        <thead>
        <tr>
            <th class="column-lg">Label</th>
            <th>Type</th>
            <th class="column-sm">Required</th>
            <th class="column-md">On Registration</th>
            <th class="column-md">In Profiles</th>
            <!--<th>In Discussions</th>-->
            <th class="column-sm"></th>
        </tr>
        </thead>
        <tbody>

        <?php foreach ($Fields as $Name => $Field) : ?>
            <tr>
                <td><div class="strong"><?php echo $Field['Label']; ?></div></td>
                <td><?php echo $Field['FormType']; ?></td>
                <td><?php echo (val('Required', $Field, 0)) ? t('Yes') : t('No'); ?></td>
                <td><?php echo (val('OnRegister', $Field, 0)) ? t('Yes') : t('No'); ?></td>
                <td><?php echo (val('OnProfile', $Field, 1)) ? t('Yes') : t('No'); ?></td>
                <!--<td><?php echo (val('OnDiscussion', $Field, 0)) ? t('Yes') : t('No'); ?></td>-->
                <td class="options">
                    <div class="btn-group">
                    <?php
                    echo anchor(dashboardSymbol('edit'), '/settings/profilefieldaddedit/'.$Name, 'js-modal btn btn-icon', ['aria-label' => t('Edit'), 'title' => t('Edit')]);
                    echo anchor(dashboardSymbol('delete'), '/settings/profilefielddelete/'.$Name, 'js-modal btn btn-icon',
                        ['aria-label' => t('Delete'), 'title' => t('Delete'), 'data-css-class' => 'modal-sm modal-confirm']);
                    ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
