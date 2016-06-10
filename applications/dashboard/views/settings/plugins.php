<?php if (!defined('APPLICATION')) exit();
$session = Gdn::session();
$updateUrl = c('Garden.UpdateCheckUrl');
$addonUrl = c('Garden.AddonUrl');
if ($this->addonType !== 'applications') {
    $this->addonType = 'plugins';
    $title = t('Manage Plugins');
    $pathHelp = sprintf(
        t('PluginHelp'),
        '<code>'.PATH_PLUGINS.'</code>'
    );
    $getMore = wrap(Anchor(t('Get More Plugins'), $addonUrl), 'li');
    $availableAddons = $this->AvailablePlugins;
    $enabledAddons = $this->EnabledPlugins;
} else {
    $title = t('Manage Applications');
    $pathHelp = sprintf(
        t('ApplicationHelp'),
        '<code>'.PATH_APPLICATIONS.'</code>'
    );
    $getMore = wrap(Anchor(t('Get More Applications').' <span class="icon icon-external-link"></span>', $addonUrl), 'li');
    $availableAddons = $this->AvailableApplications;
    $enabledAddons = $this->EnabledApplications;
}
$addonCount = count($availableAddons);
$enabledCount = count($enabledAddons);
$disabledCount = $addonCount - $enabledCount;

?>
<h1><?php echo $title; ?></h1>
<div class="Info">
    <?php echo $pathHelp; ?>
</div>
<div class="Tabs FilterTabs">
    <ul>
        <li<?php echo $this->Filter == 'all' ? ' class="Active"' : ''; ?>><?php echo anchor(sprintf(t('All %1$s'), wrap($addonCount)), 'settings/'.$this->addonType.'/all', ''.$this->addonType.'-all'); ?></li>
        <li<?php echo $this->Filter == 'enabled' ? ' class="Active"' : ''; ?>><?php echo anchor(sprintf(t('Enabled %1$s'), wrap($enabledCount)), 'settings/'.$this->addonType.'/enabled', ''.$this->addonType.'-enabled'); ?></li>
        <li<?php echo $this->Filter == 'disabled' ? ' class="Active"' : ''; ?>><?php echo anchor(sprintf(t('Disabled %1$s'), wrap($disabledCount)), 'settings/'.$this->addonType.'/disabled', ''.$this->addonType.'-disabled'); ?></li>
        <?php
        if ($addonUrl != '')
            echo $getMore;
        ?>
    </ul>
</div>
<?php echo $this->Form->errors(); ?>
<div class="Messages Errors TestAddonErrors Hidden">
    <ul>
        <li><?php echo t('The addon could not be enabled because it generated a fatal error: <pre>%s</pre>'); ?></li>
    </ul>
</div>
<ul class="media-list addon-list">
    <?php
    $Alt = false;
    foreach ($availableAddons as $addonName => $addonInfo) {
        // Skip Hidden & Trigger plugins
        if (isset($addonInfo['Hidden']) && $addonInfo['Hidden'] === TRUE)
            continue;
        if (isset($addonInfo['Trigger']) && $addonInfo['Trigger'] == TRUE) // Any 'true' value.
            continue;

        $Css = array_key_exists($addonName, $enabledAddons) ? 'Enabled' : 'Disabled';
        $State = strtolower($Css);
        if ($this->Filter == 'all' || $this->Filter == $State) {
            $Alt = !$Alt;
            $Version = Gdn_Format::Display(val('Version', $addonInfo, ''));
            $ScreenName = Gdn_Format::Display(val('Name', $addonInfo, $addonName));
            $SettingsUrl = $State == 'enabled' ? val('SettingsUrl', $addonInfo, '') : '';
            $PluginUrl = val('PluginUrl', $addonInfo, '');
            $Author = val('Author', $addonInfo, '');
            $AuthorUrl = val('AuthorUrl', $addonInfo, '');
            $NewVersion = val('NewVersion', $addonInfo, '');
            $Upgrade = $NewVersion != '' && version_compare($NewVersion, $Version, '>');
            $RowClass = $Css;
            if ($Alt) {
                $RowClass .= ' Alt';
            }

            $IconPath = val('IconUrl', $addonInfo, '/applications/dashboard/design/images/plugin-icon.png');

            ?>
            <li <?php echo 'id="'.Gdn_Format::url(strtolower($addonName)).'-plugin"', ' class="media More '.$RowClass.'"'; ?>>
                <div class="media-left">
                <?php echo wrap(img($IconPath, array('class' => 'PluginIcon')), 'div', ['class' => 'addon-image-wrap']); ?>
                </div>
                <div class="media-body">
                <div class="media-heading"><div class="media-title"><?php echo $ScreenName; ?></div>
                    <div class="info"><?php
                        $Info = [];

                        $RequiredApplications = val('RequiredApplications', $addonInfo, false);
                        $RequiredPlugins = val('RequiredPlugins', $addonInfo, false);
                        $requirements = '';
                        if (is_array($RequiredApplications) || is_array($RequiredPlugins)) {
                            $requirements = t('Requires: ');
                        }
                        $i = 0;
                        if (is_array($RequiredApplications)) {
                            if ($i > 0)
                                $requirements .= ', ';

                            foreach ($RequiredApplications as $RequiredApplication => $VersionInfo) {
                                $requirements .= sprintf(t('%1$s Version %2$s'), $RequiredApplication, $VersionInfo);
                                ++$i;
                            }
                        }
                        if ($RequiredPlugins !== FALSE) {
                            foreach ($RequiredPlugins as $RequiredPlugin => $VersionInfo) {
                                if ($i > 0)
                                    $requirements .= ', ';

                                $requirements .= sprintf(t('%1$s Version %2$s'), $RequiredPlugin, $VersionInfo);
                                ++$i;
                            }
                        }

                        if ($requirements != '') {
                            $Info[] = $requirements;
                        }

                        if ($Author != '') {
                            $Info[] = sprintf(t('Created by %s'), $AuthorUrl != '' ? anchor($Author, $AuthorUrl) : $Author);
                        }

                        if ($Version != '') {
                            $Info[] = sprintf(t('Version %s'), $Version);
                        }

                        if ($PluginUrl != '') {
                            $Info[] = anchor(t('Visit Site'), $PluginUrl);
                        }

                        echo implode('<span class="spacer">•</span>', $Info);

                        ?>
                        <?php
                        if ($Upgrade) {
                            ?>
                            <div class="<?php echo $RowClass; ?>">
                                <div class="Alert"><a href="<?php
                                    echo CombinePaths(array($updateUrl, 'find', urlencode($ScreenName)), '/');
                                    ?>"><?php
                                        printf(t('%1$s version %2$s is available.'), $ScreenName, $NewVersion);
                                        ?></a></div>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
                <div class="media-description"><?php echo Gdn_Format::Html(t(val('Name', $addonInfo, $addonName).' Description', val('Description', $addonInfo, ''))); ?></div>
                </div>
                <div class="media-right media-options">
                    <?php if ($SettingsUrl != '') {
                        echo wrap(anchor('<span class="icon icon-edit">', $SettingsUrl, 'btn btn-secondary Button', ['aria-label' => sprintf(t('Settings for %s'), $ScreenName)]), 'div', ['class' => 'btn-wrap']);
                    }
                    ?>
                    <div id="<?php echo $addonName; ?>-toggle">
                    <?php
                    $Enabled = array_key_exists($addonName, $enabledAddons);
                    if ($Enabled) {
                        $SliderState = 'Active';
                        $toggleState = 'on';
                        echo wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/settings/'.$this->addonType.'/'.$this->Filter.'/'.$addonName.'/'.$session->TransientKey(), '', ['aria-label' =>sprintf(t('Disable %s'), $ScreenName)]), 'span', array('class' => "toggle-wrap toggle-wrap-{$toggleState} ActivateSlider-{$SliderState}"));
                    } else {
                        $SliderState = 'InActive';
                        $toggleState = 'off';
                        echo wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/settings/'.$this->addonType.'/'.$this->Filter.'/'.$addonName.'/'.$session->TransientKey(), '', ['aria-label' =>sprintf(t('Enable %s'), $ScreenName)]), 'span', array('class' => "toggle-wrap toggle-wrap-{$toggleState} ActivateSlider-{$SliderState}"));
                    } ?>
                    </div>
                </div>
            </li>
    <?php }
    } ?>
</ul>
