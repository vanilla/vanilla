<?php if (!defined('APPLICATION')) exit(); ?>
<div id="RecentActivity" class="Box">
   <h4><?php echo GetValue('ActivityModuleTitle', $this, T('Recent Activity')); ?></h4>
   <ul class="PanelInfo">
      <?php
      $Data = $this->ActivityData;
      foreach ($Data->Result() as $Activity) {
         $PhotoAnchor = Anchor(
            Img($Activity['Photo'], array('class' => 'ProfilePhotoSmall')),
            $Activity['PhotoUrl'], 'Photo');
         
         echo '<li class="Activity ' . $Activity['ActivityType'] . '">';
         echo $PhotoAnchor.' '.$Activity['Headline'];
         echo '</li>';
      }
      
      if ($Data->NumRows() >= $this->Limit) {
      ?>
      <li class="ShowAll"><?php echo Anchor(T('More…'), '/activity'); ?></li>
      <?php
      }
      ?>
   </ul>
</div>