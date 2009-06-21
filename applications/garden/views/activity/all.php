<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo Gdn::Translate('Recent Activity'); ?></h1>
<ul class="Activities">
<?php
   if ($this->ActivityData->NumRows() > 0) {
      include($this->FetchViewLocation('activities', 'activity', 'garden'));
   } else {
      ?>
   <li class="Empty">
      <h2><?php echo Gdn::Translate('Not much happening here, yet.'); ?></h2>
   </li>
      <?php
   }
?>
</ul>