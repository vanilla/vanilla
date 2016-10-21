<?php if (!defined('APPLICATION')) exit(); ?>
<?php
echo heading($this->data('Title'), t('Add Item'), '/dashboard/settings/bans/add', 'btn btn-primary js-modal');
helpAsset(sprintf(t('About %s'), t('Banning')), t('You can ban IP addresses, email domains, and words from usernames using this tool.'));

echo '<noscript><div class="Errors"><ul><li>', t('This page requires Javascript.'), '</li></ul></div></noscript>';
echo $this->Form->open();
echo '<div id="BansTable">';
include dirname(__FILE__).'/banstable.php';
echo '</div id="BansTable">';

echo $this->Form->close();
