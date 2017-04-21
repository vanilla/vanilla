<?php if (!defined('APPLICATION')) exit();
echo heading($this->data('Title'), t('Add Ban Rule'), '/dashboard/settings/bans/add', 'btn btn-primary js-modal');
$help = t('You can ban IP addresses, email addresses and usernames.');
$help .= ' '.t('Specify a partial or full match when creating a ban.');
$help .= ' '.t('For example, you can ban all users with emails addresses from "example.com" by adding an email-type ban with the value "*@example.com".');
$help .= ' '.t('You can ban all users with an IP addresses prefixed with "111.111.111" by adding an IP-type ban with the value "111.111.111.*".');
helpAsset(sprintf(t('About %s'), t('Ban Rules')), $help);

echo '<noscript><div class="Errors"><ul><li>', t('This page requires Javascript.'), '</li></ul></div></noscript>';
echo $this->Form->open();


if (empty($this->data('Bans', []))) {
    $title = $this->data('EmptyMessageTitle', $this->data('EmptyMessage', t('No Ban Rules Found')));
    $body = $this->data('EmptyMessageBody', t('Use the button at the top of the page to create a ban rule.'));
    echo hero($title, $body);
} else {
    echo '<div id="BansTable">';
    include __DIR__.'/banstable.php';
    echo '</div>';
}

echo $this->Form->close();
