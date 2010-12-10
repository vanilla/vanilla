<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Tabs SearchTabs"><?php
	$Form = Gdn::Factory('Form');
	$Form->InputPrefix = '';
	echo  $Form->Open(array('action' => Url('/search'), 'method' => 'get')),
		$Form->TextBox('Search'),
		$Form->Button('Search', array('Name' => '')),
		$Form->Close();
	
	if ($this->SearchResults) {
?>
	<div class="SubTab"><?php printf(T(count($this->SearchResults) == 0 ? "↳ No results for '%s'" : "↳ Search results for '%s'"), $this->SearchTerm); ?></div>
<?php } ?>
</div>
<?php
if (is_array($this->SearchResults) && count($this->SearchResults) > 0) {
   echo $this->Pager->ToString('less');
   $ViewLocation = $this->FetchViewLocation('results');
   include($ViewLocation);
   $this->Pager->Render();
}