<?php if (!defined('APPLICATION')) exit();
$Form = new Form();
$Form->InputPrefix = '';
echo
   $Form->Open(array('action' => Url('/search'), 'method' => 'get')),
   $Form->TextBox('Search'),
   $Form->Button('Go', array('Name' => '')),
   $Form->Close();