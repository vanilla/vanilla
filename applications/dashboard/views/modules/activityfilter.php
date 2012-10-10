<?php if (!defined('APPLICATION')) exit();

$Controller = Gdn::Controller();
$Session = Gdn::Session();
$ModPermission = $Session->CheckPermission('Garden.Moderation.Manage');
$AdminPermission = $Session->CheckPermission('Garden.Settings.Manage');
if (!$ModPermission && !$AdminPermission)
   return;

?>
<div class="BoxFilter BoxActivityFilter">
   <ul class="FilterMenu">
      <li <?php if ($Controller->Data('Filter') == 'public') echo 'class="Active"'; ?>>
         <?php
         echo Anchor(Sprite('SpActivity').' '.T('Recent Activity'), '/activity');
         ?>
      </li>
      <?php
      if ($ModPermission): 
      ?>
      <li <?php if ($Controller->Data('Filter') == 'mods') echo 'class="Active"'; ?>>
         <?php
         echo Anchor(Sprite('SpMod').' '.T('Moderator Activity'), '/activity/mods');
         ?>
      </li>
      <?php
      endif;
      
      if ($AdminPermission):
      ?>
      <li <?php if ($Controller->Data('Filter') == 'admins') echo 'class="Active"'; ?>>
         <?php
         echo Anchor(Sprite('SpDashboard').' '.T('Administrator Activity'), '/activity/admins');
         ?>
      </li>
      <?php endif; ?>
   </ul>
</div>