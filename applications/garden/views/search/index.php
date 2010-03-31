<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Box SearchBox"><?php
	$Form = Gdn::Factory('Form');
	$Form->InputPrefix = '';
	echo 
		$Form->Open(array('action' => Url('/search'), 'method' => 'get')),
		$Form->TextBox('Search'),
		$Form->Button('Search', array('Name' => '')),
		$Form->Close();
?></div>

<h1><?php
if ($this->SearchResults)
	printf(T($this->SearchResults->NumRows() == 0 ? "No results for '%s'" : "Search results for '%s'"), $this->SearchTerm);
?></h1>
<?php
if ($this->SearchResults && $this->SearchResults->NumRows() > 0) {
   echo $this->Pager->ToString('less');
   $ViewLocation = $this->FetchViewLocation('results');
   include($ViewLocation);
   $this->Pager->Render();
}