<?php if (!defined('APPLICATION')) exit(); ?>
<?php
echo heading(t('Social Integration'));
$desc = t('Here is a list of all your social addons.',
    "Here's a list of all your social addons. You can enable, disable, and configure them from this page.");
helpAsset(t("What's This?"), $desc);
?>
<?php include('connection_functions.php'); ?>

<ul class="media-list media-list-connections">
<?php foreach ($this->data('Connections') as $Key => $Row) {
    WriteConnection($Row);
} ?>
</ul>
