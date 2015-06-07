<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
$AddonUrl = Gdn::config('Garden.AddonUrl');
?>
    <div class="Help Aside">
        <?php
        echo '<h2>', t('Need More Help?'), '</h2>';
        echo '<ul>';
        echo wrap(Anchor(t("Video tutorial on managing appearance"), 'settings/tutorials/appearance'), 'li');
        echo wrap(Anchor(t('Theming Overview'), 'http://docs.vanillaforums.com/theming/'), 'li');
        echo wrap(Anchor(t('Quick-Start Guide to Creating Themes for Vanilla'), 'http://docs.vanillaforums.com/theming/quickstart/'), 'li');
        echo '</ul>';
        ?>
    </div>
    <h1><?php echo t('Manage Themes'); ?></h1>
    <div class="Info">
        <?php
        printf(
            t('ThemeHelp'),
            '<code>'.PATH_THEMES.'</code>'
        );
        ?></div>
<?php
if ($AddonUrl != '')
    echo '<div class="FilterMenu">',
    anchor(t('Get More Themes'), $AddonUrl, 'SmallButton'),
    '</div>';

?>
<?php echo $this->Form->errors(); ?>
    <div class="Messages Errors TestAddonErrors Hidden">
        <ul>
            <li><?php echo t('The addon could not be enabled because it generated a fatal error: <pre>%s</pre>'); ?></li>
        </ul>
    </div>
    <div class="CurrentTheme">
        <h3><?php echo t('Current Theme'); ?></h3>
        <?php
        $Version = $this->data('EnabledTheme.Version');
        $ThemeUrl = $this->data('EnabledTheme.Url');
        $Author = $this->data('EnabledTheme.Author');
        $AuthorUrl = $this->data('EnabledTheme.AuthorUrl');
        $NewVersion = $this->data('EnabledTheme.NewVersion');
        $Upgrade = $NewVersion != '' && version_compare($NewVersion, $Version, '>');

        $PreviewUrl = $this->data('EnabledTheme.ScreenshotUrl', false);
        if ($PreviewUrl !== FALSE)
            echo img($PreviewUrl, array('alt' => $this->data('EnabledThemeName'), 'height' => '112', 'width' => '150'));

        echo '<h4>';
        echo $ThemeUrl != '' ? anchor($this->data('EnabledThemeName'), $ThemeUrl) : $this->data('EnabledThemeName');
        if ($Version != '')
            echo '<span class="Version">'.sprintf(t('version %s'), $Version).'</span>';

        if ($Author != '')
            echo '<span class="Author">'.sprintf('by %s', $AuthorUrl != '' ? anchor($Author, $AuthorUrl) : $Author).'</span>';

        echo '</h4>';
        echo '<div class="Description">'.GetValue('Description', $this->data('EnabledTheme'), '').'</div>';
        if ($this->data('EnabledTheme.Options')) {
            $OptionsDescription = sprintf(t('This theme has additional options.', 'This theme has additional options on the %s page.'),
                anchor(t('Theme Options'), '/dashboard/settings/themeoptions'));

            echo '<div class="Options">',
            $OptionsDescription,
            '</div>';

        }

        $this->fireEvent('AfterCurrentTheme');

        $RequiredApplications = val('RequiredApplications', $this->data('EnabledTheme'), false);
        if (is_array($RequiredApplications)) {
            echo '<div class="Requirements">'.t('Requires: ');

            $i = 0;
            if ($i > 0)
                echo ', ';

            foreach ($RequiredApplications as $RequiredApplication => $VersionInfo) {
                printf(t('%1$s Version %2$s'), $RequiredApplication, $VersionInfo);
                ++$i;
            }
            echo '</div>';
        }

        if ($Upgrade) {
            echo '<div class="Alert">';
            echo url(
                sprintf(t('%1$s version %2$s is available.'), $this->data('EnabledThemeName'), $NewVersion),
                CombinePaths(array($AddonUrl, 'find', urlencode($this->data('EnabledThemeName'))), '/')
            );
            echo '</div>';
        }
        ?>
    </div>
<?php if (count($this->data('AvailableThemes', array())) > 1) { ?>
    <div class="BrowseThemes">
        <h3><?php echo t('Other Themes'); ?></h3>
        <table class="SelectionGrid Themes">
            <tbody>
            <?php
            $Alt = FALSE;
            $Cols = 3;
            $Col = 0;

            foreach ($this->data('AvailableThemes') as $ThemeName => $ThemeInfo) {
                $ScreenName = val('Name', $ThemeInfo, $ThemeName);
                $ThemeFolder = val('Folder', $ThemeInfo, '');
                $Active = $ThemeFolder == $this->data('EnabledThemeFolder');
                if (!$Active) {
                    $Version = val('Version', $ThemeInfo, '');
                    $ThemeUrl = val('Url', $ThemeInfo, '');
                    $Author = val('Author', $ThemeInfo, '');
                    $AuthorUrl = val('AuthorUrl', $ThemeInfo, '');
                    $NewVersion = val('NewVersion', $ThemeInfo, '');
                    $Upgrade = $NewVersion != '' && version_compare($NewVersion, $Version, '>');
                    $PreviewUrl = val('ScreenshotUrl', $ThemeInfo, false);

                    $Col++;
                    if ($Col == 1) {
                        $ColClass = 'FirstCol';
                        echo '<tr>';
                    } elseif ($Col == 2) {
                        $ColClass = 'MiddleCol';
                    } else {
                        $ColClass = 'LastCol';
                        $Col = 0;
                    }
                    $ColClass .= $Active ? ' Enabled' : '';
                    $ColClass .= $PreviewUrl ? ' HasPreview' : '';
                    ?>
                    <td class="<?php echo $ColClass; ?>">
                        <?php
                        echo '<h4>';
                        echo $ThemeUrl != '' ? anchor($ScreenName, $ThemeUrl) : $ScreenName;
                        /*
                 if ($Version != '')
                    $Info = sprintf(t('Version %s'), $Version);

                 if ($Author != '')
                    $Info .= sprintf('by %s', $AuthorUrl != '' ? anchor($Author, $AuthorUrl) : $Author);
                        */
                        echo '</h4>';

                        if ($PreviewUrl !== FALSE) {
                            echo anchor(Img($PreviewUrl, array('alt' => $ScreenName, 'height' => '112', 'width' => '150')),
                                'dashboard/settings/previewtheme/'.$ThemeName,
                                '',
                                array('target' => '_top')
                            );
                        }

                        echo '<div class="Buttons">';
                        echo anchor(t('Apply'), 'dashboard/settings/themes/'.$ThemeName.'/'.$Session->TransientKey(), 'SmallButton EnableAddon EnableTheme', array('target' => '_top'));
                        echo anchor(t('Preview'), 'dashboard/settings/previewtheme/'.$ThemeName, 'SmallButton PreviewAddon', array('target' => '_top'));
                        $this->EventArguments['ThemeInfo'] = $ThemeInfo;
                        $this->fireEvent('AfterThemeButtons');
                        echo '</div>';

                        $Description = val('Description', $ThemeInfo);
                        if ($Description)
                            echo '<em>'.$Description.'</em>';

                        $RequiredApplications = val('RequiredApplications', $ThemeInfo, false);
                        if (is_array($RequiredApplications)) {
                            echo '<dl>
                        <dt>'.t('Requires: ').'</dt>
                        <dd>';

                            $i = 0;
                            foreach ($RequiredApplications as $RequiredApplication => $VersionInfo) {
                                if ($i > 0)
                                    echo ', ';

                                printf(t('%1$s %2$s'), $RequiredApplication, $VersionInfo);
                                ++$i;
                            }
                            echo '</dl>';
                        }

                        if ($Upgrade) {
                            echo '<div class="Alert">';
                            echo anchor(
                                sprintf(t('%1$s version %2$s is available.'), $ScreenName, $NewVersion),
                                CombinePaths(array($AddonUrl, 'find', urlencode($ThemeName)), '/')
                            );
                            echo '</div>';
                        }
                        ?>
                    </td>
                    <?php
                    if ($Col == 0)
                        echo '</tr>';
                }
            }
            // Close the row if it wasn't a full row.
            if ($Col > 0)
                echo '<td class="LastCol EmptyCol"'.($Col == 1 ? ' colspan="2"' : '').'>&#160;</td></tr>';
            ?>
            </tbody>
        </table>
    </div>
<?php
}
