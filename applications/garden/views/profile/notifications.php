<?php if (!defined('APPLICATION')) exit();
if ($this->ActivityData->NumRows() > 0) {
   echo '<ul class="Activities Notifications">';
   include($this->FetchViewLocation('activities', 'activity', 'garden'));
   echo '</ul>';
} else {
   ?>
<div class="Info EmptyInfo"><?php echo Gdn::Translate('You do not have any notifications yet.'); ?></div>
   <?php
}

/*
 $Session = Gdn::Session();
?>
<h2><?php echo Gdn::Translate('Notifications'); ?></h2>
<?php
if ($this->NotificationData->NumRows() > 0) {
?>
<ul class="Activities">
   <?php
   foreach ($this->NotificationData->Result() as $Activity) {
      ?>
      <li id="Activity_<?php echo $Activity->ActivityID; ?>" class="Activity<?php echo ' ' . $Activity->ActivityType; ?>"><?php
      if ($Activity->ActivityPhoto != '' && $Activity->ShowIcon == '1') {
         if ($Activity->InsertUserID == $Session->UserID) {
            echo '<a href="'.Url('/garden/profile/'.urlencode($Activity->ActivityName)).'">'
               .$this->Html->Image('uploads/n'.$Activity->ActivityPhoto)
            .'</a>';
         } else {
            echo $this->Html->Image('uploads/n'.$Activity->ActivityPhoto);
         }
      }
      ?><h3><strong><?php
         echo Format::ActivityHeadline($Activity, $Session->UserID);
      ?><em><?php
         echo Format::Date($Activity->DateInserted);
      ?></em></strong></h3>
      <?php
      if ($Activity->Story != '') {
      ?><blockquote><?php
         echo Format::Html($Activity->Story);
      ?></blockquote>
      <?php
      }
      ?>
      </li>
   <?php
   }
   ?>
</ul>
<?php
} else {
   echo '<div class="Info EmptyInfo">'.Translate('You do not have any notifications.').'</div>';  
}
*/