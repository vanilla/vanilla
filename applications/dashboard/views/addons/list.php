<?php if (!defined('APPLICATION')) exit(); ?>
<?php
echo heading($this->data('title'));
helpAsset($this->data('help.title'), $this->data('help.description'));
?>
<?php require_once($this->fetchViewLocation('helper_functions', 'settings', 'dashboard')); ?>

<ul class="media-list media-list-connections">
<?php foreach ($this->data('addons') as $addonName => $addonInfo) {
    writeAddonMedia($addonName, $addonInfo, $addonInfo['enabled'], 'plugins', 'all');
} ?>
</ul>
