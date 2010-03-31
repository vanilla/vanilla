<?php if (!defined('APPLICATION')) exit();
?>
<h2><?php echo T('Conversations'); ?></h2>
<?php
if ($this->ConversationData->NumRows() > 0) {
?>
<ul id="Conversations">
   <?php
   $ViewLocation = $this->FetchViewLocation('conversations');
   include($ViewLocation);
   ?>
</ul>
<?php
echo $this->Pager->ToString();
} else {
   echo '<div class="Info EmptyInfo">'.T('You do not have any conversations.').'</div>';
}
