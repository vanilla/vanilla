<?php if (!defined('APPLICATION')) exit();
$Form = Gdn::Factory('Form');
$Form->InputPrefix = '';
echo 
   $Form->Open(array('action' => Url('/search'), 'method' => 'get')),
   $Form->TextBox('Search'),
   $Form->Button('Go', array('Name' => '')),
   $Form->Close();
