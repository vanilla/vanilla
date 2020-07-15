<?php if (!defined('APPLICATION')) exit();
$name = $Data['Profile']['Name'] ?? '';
$appendName = '';
if ($name) {
    $name = urlencode($name);
    $appendName = '/'.$name;
}
echo anchor(t('New Message'), '/messages/add'.$appendName, 'Button BigButton NewConversation Primary', ['title' => t(sprintf('Send a message to \'%s\'', $name)), 'aria-label' => t(sprintf('Send a message to \'%s\'', $name))]);
