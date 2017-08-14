<?php if (!defined('APPLICATION')) exit();
$session = Gdn::session();
$updateUrl = c('Garden.UpdateCheckUrl');
$addonUrl = c('Garden.AddonUrl');
$addonType = val('addonType', $this);
if ($addonType === 'applications') {
    $title = '<h1>'.t('Manage Applications').'</h1>';
    $helpTitle = sprintf(t('About %s'), t('Applications'));
    $pathHelp = sprintf(
        t('ApplicationHelp'),
        '<code>'.PATH_APPLICATIONS.'</code>'
    );
    $getMore = wrap(anchor(t('Get More Applications').' <span class="icon icon-external-link"></span>', $addonUrl), 'li');
    $availableAddons = $this->AvailableApplications;
    $enabledAddons = $this->EnabledApplications;

} elseif ($addonType === 'locales') {
    $title = '';
    $helpTitle = sprintf(t('About %s'), t('Locales'));
    $pathHelp = sprintf(
        t('LocaleHelp', 'Locales allow you to support other languages on your site. Once a locale has been added to your %s folder, you can enable or disable it here.'),
        '<code>'.PATH_ROOT.'/locales</code>'
    );
    $getMore = wrap(anchor(t('Get More Locales').' <span class="icon icon-external-link"></span>', $addonUrl), 'li');
    $availableAddons = $this->data('AvailableLocales');
    $enabledAddons = $this->data('EnabledLocales');
    $this->Filter = 'all';
} else {
    $this->addonType = 'plugins';
    $title = '<h1>'.t('Manage Plugins').'</h1>';
    $helpTitle = sprintf(t('About %s'), t('Plugins'));
    $pathHelp = sprintf(
        t('PluginHelp'),
        '<code>'.PATH_PLUGINS.'</code>'
    ). '<br/><br/>' . anchor(t('Addon Documentation'), 'http://docs.vanillaforums.com/help/addons/', '', ["target" => "_blank"]);
    $getMore = wrap(anchor(t('Get More Plugins').' <span class="icon icon-external-link"></span>', $addonUrl), 'li');
    $availableAddons = $this->AvailablePlugins;
    $enabledAddons = $this->EnabledPlugins;
}

$addonCount = count($availableAddons);
$enabledCount = count($enabledAddons);
$disabledCount = $addonCount - $enabledCount;

$this->EventArguments['AvailableAddons'] = &$availableAddons;
$this->fireAs('SettingsController');
$this->fireEvent('BeforeAddonList');

helpAsset($helpTitle, $pathHelp);
?>
<?php echo $title; ?>
<?php if ($this->addonType !== 'locales') { ?>
    <div class="toolbar">
<div class="btn-group filters">
        <?php $active = $this->Filter == 'all' ? 'active' : ''; ?><?php echo anchor(sprintf(t('All %1$s'), wrap($addonCount, 'span', ['class' => 'badge'])), 'settings/'.$this->addonType.'/all', ''.$this->addonType.'-all btn btn-secondary '.$active); ?>
        <?php $active = $this->Filter == 'enabled' ? 'active' : ''; ?><?php echo anchor(sprintf(t('Enabled %1$s'), wrap($enabledCount, 'span', ['class' => 'badge'])), 'settings/'.$this->addonType.'/enabled', ''.$this->addonType.'-enabled btn btn-secondary '.$active); ?>
        <?php $active =  $this->Filter == 'disabled' ? 'active' : ''; ?><?php echo anchor(sprintf(t('Disabled %1$s'), wrap($disabledCount, 'span', ['class' => 'badge'])), 'settings/'.$this->addonType.'/disabled', ''.$this->addonType.'-disabled btn btn-secondary '.$active); ?>
<!--        --><?php //if ($addonUrl != '') echo $getMore; ?>
</div>
    </div>
<?php } ?>
<?php echo $this->Form->errors(); ?>
<div class="Messages Errors TestAddonErrors Hidden">
    <ul>
        <li><?php echo t('The addon could not be enabled because it generated a fatal error: <pre>%s</pre>'); ?></li>
    </ul>
</div>
<ul class="media-list addon-list">
    <?php
    require_once($this->fetchViewLocation('helper_functions'));
    foreach ($availableAddons as $addonName => $addonInfo) {
        $isEnabled = array_key_exists($addonName, $enabledAddons);
        // Skip Hidden & Trigger plugins
        if ((isset($addonInfo['Hidden']) && $addonInfo['Hidden'] === true)
            || (isset($addonInfo['Trigger']) && $addonInfo['Trigger'] == true)
            || ($this->Filter === 'disabled' && $isEnabled)
            || ($this->Filter === 'enabled' && !$isEnabled)) {
            echo '';
        } else {
            writeAddonMedia($addonName, $addonInfo, $isEnabled, $this->addonType, $this->Filter);
        }
    } ?>
</ul>
