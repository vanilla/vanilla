<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Box Moderators">
   <h4><?php echo T('Moderators'); ?></h4>
   <ul class="PanelInfo">
      <?php
      foreach ($this->ModeratorData[0]->Moderators as $Mod) {
         $Mod = UserBuilder($Mod);
         echo '<li>'.UserPhoto($Mod, 'Small').' '.UserAnchor($Mod).'</li>';
      }
      ?>
   </ul>
</div>