<?php if (!defined('APPLICATION')) exit();
?>
<h2><?php echo Gdn::Translate('Conversations'); ?></h2>
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
   echo '<div class="Info EmptyInfo">'.Gdn::Translate('You do not have any conversations.').'</div>';
}
