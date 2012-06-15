<?php if (!defined('APPLICATION')) exit();
?>
<h1 class="H"><?php echo $this->Data('Title'); ?></h1>
<?php
if ($this->ConversationData->NumRows() > 0) {
?>
<ul class="Condensed DataList Conversations">
   <?php
   $ViewLocation = $this->FetchViewLocation('conversations');
   include($ViewLocation);
   ?>
</ul>
<?php

PagerModule::Write();


} else {
   echo '<div class="Empty">'.T('You do not have any conversations.').'</div>';
}
