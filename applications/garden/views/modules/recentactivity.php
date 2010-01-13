<?php if (!defined('APPLICATION')) exit(); ?>
<div id="RecentActivity" class="Box">
   <h4><?php echo Gdn::Translate('Recent Activity'); ?></h4>
   <ul class="PanelActivity">
      <?php
      foreach ($this->_ActivityData->Result() as $Activity) {
         echo '<li class="Activity ' . $Activity->ActivityType . '">';
         // If this was a status update or a wall comment, don't bother with activity strings
         $ActivityType = explode(' ', $Activity->ActivityType); // Make sure you strip out any extra css classes munged in here
         $ActivityType = $ActivityType[0];
         $Author = new stdClass();
         $Author->UserID = $Activity->ActivityUserID;
         $Author->Name = $Activity->ActivityName;
         $Author->Photo = $Activity->ActivityPhoto;
         if (in_array($ActivityType, array('WallComment', 'AboutUpdate'))) {
            echo UserAnchor($Author, 'Name');
            if ($Activity->ActivityType == 'WallComment' && $Activity->RegardingUserID > 0) {
               $Author->UserID = $Activity->RegardingUserID;
               $Author->Name = $Activity->RegardingName;
               $Author->Photo = '';
               echo '<span>→</span>'.UserAnchor($Author, 'Name');
            }
            echo Format::Display($Activity->Story);
            echo '<em>'.Format::Date($Activity->DateInserted).'</em>';
         } else {
            echo Format::ActivityHeadline($Activity);
            echo '<em>'.Format::Date($Activity->DateInserted).'</em>';
            if ($Activity->Story != '') {
               echo '<div class="Story">';
                  echo $Activity->Story;
               echo '</div>';
            }
         }
         echo '</li>';
      }
      ?>
      <li class="ShowAll"><?php echo Anchor(Gdn::Translate('↳ Show All'), 'activity'); ?></li>
   </ul>
</div>