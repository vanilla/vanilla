<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
$UpdateUrl = c('Garden.UpdateCheckUrl');
$AddonUrl = c('Garden.AddonUrl');
$PluginCount = count($this->AvailablePlugins);
$EnabledCount = count($this->EnabledPlugins);
$DisabledCount = $PluginCount - $EnabledCount;
?>
<script type="text/javascript">
//    jQuery(document).ready(function($) {
//        var selectors = '.plugins-all, .plugins-enabled, .plugins-disabled';
//        $(selectors).click(function() {
//            $(selectors).parents('li').removeClass('Active');
//            $(this).parents('li').addClass('Active');
//            if ($(this).hasClass('plugins-disabled')) {
//                $('tr.Enabled').hide();
//                $('tr.Disabled').show();
//            } else if ($(this).hasClass('plugins-enabled')) {
//                $('tr.Enabled').show();
//                $('tr.Disabled').hide();
//            } else {
//                $('tr.Enabled').show();
//                $('tr.Disabled').show();
//            }
//            return false;
//        });
//    });
</script>
<h1><?php echo t('Manage Plugins'); ?></h1>
<div class="Info">
    <?php
    printf(
        t('PluginHelp'),
        '<code>'.PATH_PLUGINS.'</code>'
    );
    ?>
</div>
<div class="Tabs FilterTabs">
    <ul>
        <li<?php echo $this->Filter == 'all' ? ' class="Active"' : ''; ?>><?php echo anchor(sprintf(t('All %1$s'), wrap($PluginCount)), 'settings/plugins/all', 'plugins-all'); ?></li>
        <li<?php echo $this->Filter == 'enabled' ? ' class="Active"' : ''; ?>><?php echo anchor(sprintf(t('Enabled %1$s'), wrap($EnabledCount)), 'settings/plugins/enabled', 'plugins-enabled'); ?></li>
        <li<?php echo $this->Filter == 'disabled' ? ' class="Active"' : ''; ?>><?php echo anchor(sprintf(t('Disabled %1$s'), wrap($DisabledCount)), 'settings/plugins/disabled', 'plugins-disabled'); ?></li>
        <?php
        if ($AddonUrl != '')
            echo wrap(Anchor(t('Get More Plugins'), $AddonUrl), 'li');
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
    foreach ($this->AvailablePlugins as $PluginName => $PluginInfo) {
        // Skip Hidden & Trigger plugins
        if (isset($PluginInfo['Hidden']) && $PluginInfo['Hidden'] === TRUE)
            continue;
        if (isset($PluginInfo['Trigger']) && $PluginInfo['Trigger'] == TRUE) // Any 'true' value.
            continue;

        $Css = array_key_exists($PluginName, $this->EnabledPlugins) ? 'Enabled' : 'Disabled';
        $State = strtolower($Css);
        if ($this->Filter == 'all' || $this->Filter == $State) {
            $Alt = !$Alt;
            $Version = Gdn_Format::Display(val('Version', $PluginInfo, ''));
            $ScreenName = Gdn_Format::Display(val('Name', $PluginInfo, $PluginName));
            $SettingsUrl = $State == 'enabled' ? val('SettingsUrl', $PluginInfo, '') : '';
            $PluginUrl = val('PluginUrl', $PluginInfo, '');
            $Author = val('Author', $PluginInfo, '');
            $AuthorUrl = val('AuthorUrl', $PluginInfo, '');
            $NewVersion = val('NewVersion', $PluginInfo, '');
            $Upgrade = $NewVersion != '' && version_compare($NewVersion, $Version, '>');
            $RowClass = $Css;
            if ($Alt) {
                $RowClass .= ' Alt';
            }

            $IconPath = val('IconUrl', $PluginInfo, '/applications/dashboard/design/images/plugin-icon.png');

            ?>
            <li <?php echo 'id="'.Gdn_Format::url(strtolower($PluginName)).'-plugin"', ' class="media More '.$RowClass.'"'; ?>>
                <div class="media-left">
                <?php echo wrap(img($IconPath, array('class' => 'PluginIcon')), 'div', ['class' => 'addon-image-wrap']); ?>
                </div>
                <div class="media-body">
                <div class="media-heading"><div class="media-title"><?php echo $ScreenName; ?></div>
                    <div class="info"><?php
                        $Info = [];

                        $RequiredApplications = val('RequiredApplications', $PluginInfo, false);
                        $RequiredPlugins = val('RequiredPlugins', $PluginInfo, false);
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

                        echo implode(' • ', $Info);

                        ?>
                        <?php
                        if ($Upgrade) {
                            ?>
                            <div class="<?php echo $RowClass; ?>">
                                <div class="Alert"><a href="<?php
                                    echo CombinePaths(array($UpdateUrl, 'find', urlencode($ScreenName)), '/');
                                    ?>"><?php
                                        printf(t('%1$s version %2$s is available.'), $ScreenName, $NewVersion);
                                        ?></a></div>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
                <div class="media-description"><?php echo Gdn_Format::Html(t(val('Name', $PluginInfo, $PluginName).' Description', val('Description', $PluginInfo, ''))); ?></div>
                </div>
                <div class="media-right media-options">
                    <?php if ($SettingsUrl != '') {
                        echo wrap(anchor('<span class="icon icon-edit">', $SettingsUrl, 'btn btn-secondary Button', ['aria-label' => sprintf(t('Settings for %s'), $ScreenName)]), 'div', ['class' => 'btn-wrap']);
                    }
                    ?>
                    <div id="<?php echo $PluginName; ?>-toggle">
                    <?php
                    $Enabled = array_key_exists($PluginName, $this->EnabledPlugins);
                    if ($Enabled) {
                        $SliderState = 'Active';
                        $toggleState = 'on';
                        echo wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/settings/plugins/'.$this->Filter.'/'.$PluginName.'/'.$Session->TransientKey(), 'Hijack', ['aria-label' =>sprintf(t('Disable %s'), $ScreenName)]), 'span', array('class' => "toggle-wrap toggle-wrap-{$toggleState} ActivateSlider-{$SliderState}"));
                    } else {
                        $SliderState = 'InActive';
                        $toggleState = 'off';
                        echo wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/settings/plugins/'.$this->Filter.'/'.$PluginName.'/'.$Session->TransientKey(), 'Hijack', ['aria-label' =>sprintf(t('Enable %s'), $ScreenName)]), 'span', array('class' => "toggle-wrap toggle-wrap-{$toggleState} ActivateSlider-{$SliderState}"));
                    } ?>
                    </div>
                </div>
            </li>
    <?php }
    } ?>
</ul>
