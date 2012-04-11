<?php if (!defined('APPLICATION')) exit();
?>
<h1><?php echo $this->Data('Title'); ?></h1>
<?php
if (count($this->Data('Conversations'))) {
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
