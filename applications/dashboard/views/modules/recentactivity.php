<?php if (!defined('APPLICATION')) exit(); ?>
<div id="RecentActivity" class="Box">
   <h4><?php echo T('Recent Activity'); ?></h4>
   <ul class="PanelActivity">
      <?php
      foreach ($this->_ActivityData->Result() as $Activity) {
         echo '<li class="Activity ' . $Activity->ActivityType . '">';
         // If this was a status update or a wall comment, don't bother with activity strings
         $ActivityType = explode(' ', $Activity->ActivityType); // Make sure you strip out any extra css classes munged in here
         $ActivityType = $ActivityType[0];
         $Author = UserBuilder($Activity, 'Activity');
         if (in_array($ActivityType, array('WallComment', 'AboutUpdate'))) {
            echo UserAnchor($Author, 'Name');
            if ($Activity->ActivityType == 'WallComment' && $Activity->RegardingUserID > 0) {
               $Author = UserBuilder($Activity, 'Regarding');
               echo '<span>&rarr;</span>'.UserAnchor($Author, 'Name');
            }
            echo Gdn_Format::Display($Activity->Story);
            echo '<em>'.Gdn_Format::Date($Activity->DateInserted).'</em>';
         } else {
            echo Gdn_Format::ActivityHeadline($Activity);
            echo '<em>'.Gdn_Format::Date($Activity->DateInserted).'</em>';
            if ($Activity->Story != '') {
               echo '<div class="Story">';
                  echo $Activity->Story;
               echo '</div>';
            }
         }
         echo '</li>';
      }
      ?>
      <li class="ShowAll"><?php echo Anchor(T('↳ Show All'), 'activity'); ?></li>
   </ul>
</div>