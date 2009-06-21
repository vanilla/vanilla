<?php if (!defined('APPLICATION')) exit();
?>
<h2><?php echo Gdn::Translate('Conversations'); ?></h2>
<ul id="Conversations">
   <?php
if ($this->ConversationData->NumRows() == 0) {
   ?>
   <li class="Empty"><?php
      echo Gdn::Translate('You do not have any conversations.');
   ?></li>
   <?php
} else {
   $ViewLocation = $this->FetchViewLocation('conversations');
   include($ViewLocation);
} ?>
</ul>
<?php echo $this->Pager->ToString();
