<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$ShowOptions = TRUE;
$Alt = '';
$ViewLocation = $this->FetchViewLocation('drafts', 'drafts');
if ($this->DraftData->NumRows() > 0) {
   ?>
<h1><?php echo Gdn::Translate('My Drafts'); ?></h1>
<?php echo $this->Pager->ToString('less'); ?>
<ul class="DataList Drafts">
   <?php
   include($ViewLocation);
   ?>
</ul>
   <?php
   echo $this->Pager->ToString('more');
} else {
   echo '<p>'.Gdn::Translate('You do not have any drafts.').'</p>';
}
