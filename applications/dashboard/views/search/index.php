<?php if (!defined('APPLICATION')) exit(); ?>
<div class="SearchForm">
<?php
$Form = $this->Form;
$Form->InputPrefix = '';
echo  $Form->Open(array('action' => Url('/search'), 'method' => 'get')),
   '<div class="SiteSearch">',
   $Form->TextBox('Search'),
   $Form->Button('Search', array('Name' => '')),
   '</div>',
   $Form->Errors(),
   $Form->Close();
?>
</div>
<?php
if (!is_array($this->SearchResults) || count($this->SearchResults) == 0) {
   echo '<p class="NoResults">', sprintf(T('No results for %s.', 'No results for <b>%s</b>.'), htmlspecialchars($this->SearchTerm)), '</p>';
} else {
   $ViewLocation = $this->FetchViewLocation('results');
   include($ViewLocation);
   echo '<div class="PageControls Bottom">';
   PagerModule::Write();
   echo '</div>';
}