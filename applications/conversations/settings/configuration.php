<?php if (!defined('APPLICATION')) {
    exit();
      }
// DO NOT EDIT THIS FILE
// All of the settings defined here can be overridden in the /conf/config.php file.

$Configuration['Conversations']['Installed'] = '0';
$Configuration['Conversations']['Conversations']['PerPage'] = '50';
$Configuration['Conversations']['Messages']['PerPage'] = '50';
$Configuration['Conversations']['Message']['MaxLength'] = '2000';
$Configuration['Conversations']['Message']['Format'] = 'Text';
$Configuration['Conversations']['Subjects']['Visible'] = false;
// Flood control defaults.
$Configuration['Conversations']['Conversation']['SpamCount'] = '2';
$Configuration['Conversations']['Conversation']['SpamTime'] = '30';
$Configuration['Conversations']['Conversation']['SpamLock'] = '60';
$Configuration['Conversations']['ConversationMessage']['SpamCount'] = '2';
$Configuration['Conversations']['ConversationMessage']['SpamTime'] = '30';
$Configuration['Conversations']['ConversationMessage']['SpamLock'] = '60';
