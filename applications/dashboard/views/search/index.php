<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Tabs SearchTabs">
<?php
$Form = $this->Form;
$Form->InputPrefix = '';
echo  $Form->Open(array('action' => Url('/search'), 'method' => 'get')),
   $Form->TextBox('Search'),
   $Form->Button('Search', array('Name' => '')),
   $Form->Errors(),
   $Form->Close();
?>
</div>
<?php
if (!is_array($this->SearchResults) || count($this->SearchResults) == 0) {
   echo '<p class="NoResults">', sprintf(T('No results for %s.', 'No results for <b>%s</b>.'), htmlspecialchars($this->SearchTerm)), '</p>';
} else {
   echo $this->Pager->ToString('less');
   $ViewLocation = $this->FetchViewLocation('results');
   include($ViewLocation);
   $this->Pager->Render();
}