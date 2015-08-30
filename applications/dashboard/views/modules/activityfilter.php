<?php if (!defined('APPLICATION')) exit();

$Controller = Gdn::controller();
$Session = Gdn::session();
$ModPermission = $Session->checkPermission('Garden.Moderation.Manage');
$AdminPermission = $Session->checkPermission('Garden.Settings.Manage');
if (!$ModPermission && !$AdminPermission)
    return;

?>
<div class="BoxFilter BoxActivityFilter">
    <ul class="FilterMenu">
        <li <?php if ($Controller->data('Filter') == 'public') echo 'class="Active"'; ?>>
            <?php
            echo anchor(sprite('SpActivity').' '.t('Recent Activity'), '/activity');
            ?>
        </li>
        <?php
        if ($ModPermission):
            ?>
            <li <?php if ($Controller->data('Filter') == 'mods') echo 'class="Active"'; ?>>
                <?php
                echo anchor(sprite('SpMod').' '.t('Moderator Activity'), '/activity/mods');
                ?>
            </li>
        <?php
        endif;

        if ($AdminPermission):
            ?>
            <li <?php if ($Controller->data('Filter') == 'admins') echo 'class="Active"'; ?>>
                <?php
                echo anchor(sprite('SpDashboard').' '.t('Administrator Activity'), '/activity/admins');
                ?>
            </li>
        <?php endif; ?>
    </ul>
</div>
