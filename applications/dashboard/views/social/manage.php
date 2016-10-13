<?php if (!defined('APPLICATION')) exit(); ?>
<div class="header-block">
    <h1><?php echo t('Social Integration'); ?></h1>
</div>
<?php
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
