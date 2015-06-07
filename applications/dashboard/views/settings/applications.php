<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
$UpdateUrl = c('Garden.UpdateCheckUrl');
$AddonUrl = c('Garden.AddonUrl');
$AppCount = count($this->AvailableApplications);
$EnabledCount = count($this->EnabledApplications);
$DisabledCount = $AppCount - $EnabledCount;
?>
<h1><?php echo t('Manage Applications'); ?></h1>
<div class="Info">
    <?php
    printf(
        t('ApplicationHelp'),
        '<code>'.PATH_APPLICATIONS.'</code>'
    );
    ?>
</div>
<div class="Tabs FilterTabs">
    <ul>
        <li<?php echo $this->Filter == 'all' ? ' class="Active"' : ''; ?>><?php echo anchor(t('All').' '.Wrap($AppCount), 'settings/applications/'); ?></li>
        <li<?php echo $this->Filter == 'enabled' ? ' class="Active"' : ''; ?>><?php echo anchor(t('Enabled').' '.Wrap($EnabledCount), 'settings/applications/enabled'); ?></li>
        <li<?php echo $this->Filter == 'disabled' ? ' class="Active"' : ''; ?>><?php echo anchor(t('Disabled').' '.Wrap($DisabledCount), 'settings/applications/disabled'); ?></li>
        <?php
        if ($AddonUrl != '')
            echo wrap(Anchor(t('Get More Applications'), $AddonUrl), 'li');
        ?>
    </ul>
</div>
<?php echo $this->Form->errors(); ?>
<div class="Messages Errors TestAddonErrors Hidden">
    <ul>
        <li><?php echo t('The addon could not be enabled because it generated a fatal error: <pre>%s</pre>'); ?></li>
    </ul>
</div>
<table class="AltRows">
    <thead>
    <tr>
        <th><?php echo t('Application'); ?></th>
        <th class="Alt"><?php echo t('Description'); ?></th>
    </tr>
    </thead>
    <tbody>
    <?php
    $Alt = FALSE;
    foreach ($this->AvailableApplications as $AppName => $AppInfo) {
        $Css = array_key_exists($AppName, $this->EnabledApplications) ? 'Enabled' : 'Disabled';
        $State = strtolower($Css);
        if ($this->Filter == 'all' || $this->Filter == $State) {
            $Alt = $Alt ? FALSE : TRUE;
            $Version = arrayValue('Version', $AppInfo, '');
            $ScreenName = arrayValue('Name', $AppInfo, $AppName);
            $SettingsUrl = $State == 'enabled' ? arrayValue('SettingsUrl', $AppInfo, '') : '';
            $AppUrl = arrayValue('Url', $AppInfo, '');
            $Author = arrayValue('Author', $AppInfo, '');
            $AuthorUrl = arrayValue('AuthorUrl', $AppInfo, '');
            $NewVersion = arrayValue('NewVersion', $AppInfo, '');
            $Upgrade = $NewVersion != '' && version_compare($NewVersion, $Version, '>');
            $RowClass = $Css;
            if ($Alt) $RowClass .= ' Alt';
            ?>
            <tr class="More <?php echo $RowClass; ?>">
                <th><?php echo $ScreenName; ?></th>
                <td><?php echo arrayValue('Description', $AppInfo, ''); ?></td>
            </tr>
            <tr class="<?php echo ($Upgrade ? 'More ' : '').$RowClass; ?>">
                <td class="Info"><?php
                    $ToggleText = array_key_exists($AppName, $this->EnabledApplications) ? 'Disable' : 'Enable';
                    echo anchor(
                        t($ToggleText),
                        '/settings/applications/'.$this->Filter.'/'.$AppName.'/'.$Session->TransientKey(),
                        $ToggleText.'Addon SmallButton'
                    );

                    if ($SettingsUrl != '') {
                        echo anchor(t('Settings'), $SettingsUrl, 'SmallButton');
                    }
                    ?></td>
                <td class="Alt Info"><?php
                    $RequiredApplications = arrayValue('RequiredApplications', $AppInfo, false);
                    $Info = '';
                    if ($Version != '')
                        $Info = sprintf(t('Version %s'), $Version);

                    if (is_array($RequiredApplications)) {
                        if ($Info != '')
                            $Info .= '<span>|</span>';

                        $Info .= t('Requires: ');
                    }

                    $i = 0;
                    if (is_array($RequiredApplications)) {
                        if ($i > 0)
                            $Info .= ', ';

                        foreach ($RequiredApplications as $RequiredApplication => $VersionInfo) {
                            $Info .= sprintf(t('%1$s Version %2$s'), $RequiredApplication, $VersionInfo);
                            ++$i;
                        }
                    }

                    if ($Author != '') {
                        $Info .= '<span>|</span>';
                        $Info .= sprintf('By %s', $AuthorUrl != '' ? anchor($Author, $AuthorUrl) : $Author);
                    }

                    if ($AppUrl != '') {
                        $Info .= '<span>|</span>';
                        $Info .= anchor(t('Visit Site'), $AppUrl);
                    }

                    echo $Info != '' ? $Info : '&#160;';
                    ?>
                </td>
            </tr>
            <?php
            if ($Upgrade) {
                ?>
                <tr class="<?php echo $RowClass; ?>">
                    <td colspan="2">
                        <div class="Alert"><a href="<?php
                            echo CombinePaths(array($UpdateUrl, 'find', urlencode($AppName)), '/');
                            ?>"><?php
                                printf(t('%1$s version %2$s is available.'), $ScreenName, $NewVersion);
                                ?></a></div>
                    </td>
                </tr>
            <?php
            }
        }
    }
    ?>
    </tbody>
</table>
