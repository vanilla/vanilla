<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$ShowOptions = TRUE;
$Alt = '';
$ViewLocation = $this->FetchViewLocation('drafts', 'drafts');
?>
<h1><?php echo Gdn::Translate('My Drafts'); ?></h1>
<?php
if ($this->DraftData->NumRows() > 0) {
   echo $this->Pager->ToString('less');
?>
<ul class="DataList Drafts">
   <?php
   include($ViewLocation);
   ?>
</ul>
   <?php
   echo $this->Pager->ToString('more');
} else {
   ?>
   <div class="Info EmptyInfo"><?php echo Gdn::Translate('You do not have any drafts.'); ?></div>
   <?php
}
