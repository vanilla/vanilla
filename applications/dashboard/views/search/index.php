<?php if (!defined('APPLICATION')) exit(); ?>
<div class="SearchForm">
<?php
$Form = $this->Form;
$Form->InputPrefix = '';
echo  $Form->Open(array('action' => Url('/search'), 'method' => 'get')),
   '<div class="SiteSearch InputAndButton">',
   $Form->TextBox('Search'),
   $Form->Button('Search', array('Name' => '')),
   '</div>',
   $Form->Errors(),
   $Form->Close();
?>
</div>
<?php
$ViewLocation = $this->FetchViewLocation('results');
   include($ViewLocation);