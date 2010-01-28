<?php if (!defined('APPLICATION')) exit(); ?>
<div class="SearchBox"><?php
	$Form = Gdn::Factory('Form');
	$Form->InputPrefix = '';
	echo 
		$Form->Open(array('action' => Url('/search'), 'method' => 'get')),
		'<b>', $Form->Label("Search", "Search"), '</b>',
		$Form->TextBox('Search', array('value' => GetIncomingValue('Search'))),
		" ",
		$Form->Button('Go', array('Name' => '')),
		$Form->Close();
?></div>

<h1><?php printf(Gdn::Translate("Search results for '%s'"), $this->SearchTerm); ?></h1>
<?php
echo $this->Pager->ToString('less');

$ViewLocation = $this->FetchViewLocation('results');
include($ViewLocation);

$this->Pager->Render();
