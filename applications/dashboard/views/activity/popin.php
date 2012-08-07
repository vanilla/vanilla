<?php if (!defined('APPLICATION')) exit(); ?>
<ul class="PopList Activities">
<?php
if (count($this->Data('Activities'))):
?>
   <li class="Item Title"><?php 
      echo Anchor(T('Notification Preferences'), 'profile/preferences');
      echo Wrap(T('Notifications'), 'strong'); 
   ?></li>
   <?php foreach ($this->Data('Activities') as $Activity): ?>
   <li class="Item">
      <?php
      if ($Activity['Photo']) {
         $PhotoAnchor = Anchor(
            Img($Activity['Photo'], array('class' => 'ProfilePhoto PhotoWrapMedium')),
            $Activity['PhotoUrl'], 'PhotoWrap PhotoWrapMedium');
      } else {
         $PhotoAnchor = '';
      }
      ?>
      <div class="Author Photo"><?php echo $PhotoAnchor; ?></div>
      <div class="ItemContent Activity">
         <?php echo $Activity['Headline']; ?>
         <div class="Meta">
            <span class="MItem DateCreated"><?php echo Gdn_Format::Date($Activity['DateUpdated']); ?></span>
         </div>
      </div>
   </li>
   <?php endforeach; ?>
   <li class="Item Center">
      <?php
      echo Anchor(sprintf(T('All %s'), T('Notifications')), '/profile/notifications'); 
      ?>
   </li>
<?php else: ?>
   <li class="Item Empty Center"><?php echo T('You do not have any notifications yet.'); ?></li>
<?php endif; ?>
</ul>