<?php if (!defined('APPLICATION')) exit(); ?>
<?php
echo heading(t('Social Connect Addons'));
$desc = t('Here is a list of all your social addons.',
    "Here's a list of all your social addons. You can enable, disable, and configure them from this page.");
helpAsset(t("What's This?"), $desc);
?>
<?php require_once($this->fetchViewLocation('helper_functions', 'settings', 'dashboard')); ?>

<ul class="media-list media-list-connections">
<?php foreach ($this->data('Connections') as $addonName => $addonInfo) {
    writeAddonMedia($addonName, $addonInfo, $addonInfo['enabled'], 'plugins', 'all');
} ?>
</ul>
