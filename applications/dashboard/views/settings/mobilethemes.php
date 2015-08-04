<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
$AddonUrl = Gdn::config('Garden.AddonUrl');
?>

<h1>
    <?php echo t('Manage Mobile Themes'); ?>
</h1>

<div class="Info">
    <p class="P"><?php echo t('Mobile themes allow you to change the look and feel of your site on smaller devices.'); ?></p>

    <p class="P"><?php echo t('They work just like regular themes. Once one has been added to the themes folder, you can enable it here.'); ?></p>
</div>

<h3>Available Mobile Themes</h3>

<?php echo $this->Form->errors(); ?>

<div class="Messages Errors TestAddonErrors Hidden">
    <ul>
        <li><?php echo t('The addon could not be enabled because it generated a fatal error: <pre>%s</pre>'); ?></li>
    </ul>
</div>

<?php if (count($this->data('AvailableThemes', array()))): ?>

    <table class="browser-mobile-themes SelectionGrid Themes">
        <tbody>

        <?php
        // Get currently enabled theme data.
        $EnabledThemeInfo = $this->data('EnabledThemeInfo');
        $EnabledVersion = $this->data('EnabledTheme.Version');
        $EnabledThemeUrl = $this->data('EnabledTheme.Url');
        $EnabledAuthor = $this->data('EnabledTheme.Author');
        $EnabledAuthorUrl = $this->data('EnabledTheme.AuthorUrl');
        $EnabledNewVersion = $this->data('EnabledTheme.NewVersion');
        $EnabledUpgrade = $EnabledNewVersion != '' && version_compare($EnabledNewVersion, $EnabledVersion, '>');
        $EnabledPreviewUrl = $this->data('EnabledTheme.MobileScreenshotUrl', false);
        $EnabledThemeName = $this->data('EnabledThemeName');

        if ($this->data('EnabledTheme.Options')) {
            $OptionsDescription = sprintf(t('This theme has additional options.', 'This theme has additional options on the %s page.'), anchor(t('Theme Options'), '/dashboard/settings/themeoptions'));
        }

        $Cols = 3;
        $Col = 0;
        foreach ($this->data('AvailableThemes') as $ThemeName => $ThemeInfo):

            $ScreenName = val('Name', $ThemeInfo, $ThemeName);
            $ThemeFolder = val('Folder', $ThemeInfo, '');
            $Active = $ThemeFolder == $this->data('EnabledThemeFolder');
            $Version = val('Version', $ThemeInfo, '');
            $ThemeUrl = val('Url', $ThemeInfo, '');
            $Author = val('Author', $ThemeInfo, '');
            $AuthorUrl = val('AuthorUrl', $ThemeInfo, '');
            $NewVersion = val('NewVersion', $ThemeInfo, '');
            $Upgrade = $NewVersion != '' && version_compare($NewVersion, $Version, '>');
            $PreviewUrl = val('MobileScreenshotUrl', $ThemeInfo, false);
            $Description = val('Description', $ThemeInfo);
            $RequiredApplications = val('RequiredApplications', $ThemeInfo, false);

            $ClassCurrentTheme = ($EnabledThemeInfo['Index'] == $ThemeInfo['Index'])
                ? 'current-theme'
                : '';

            $PreviewImageHtml = ($PreviewUrl !== FALSE)
                ? anchor(Img($PreviewUrl, array('alt' => $ScreenName)), $PreviewUrl, '', array('class' => 'theme-image mfp-image'))
                : '<div class="theme-image"></div>';

            $DescriptionHtml = ($Description)
                ? '<em class="theme-description">'.$Description.'</em>'
                : '';

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

            ?>

            <td class="themeblock <?php echo $ClassCurrentTheme; ?> <?php echo $ColClass; ?>">
                <h4>
                    <?php echo ($ThemeUrl != '') ? anchor($ScreenName, $ThemeUrl) : $ScreenName; ?>
                </h4>

                <!--<div class="author-name">
               <?php echo $Author; ?>
            </div>-->

                <?php echo $PreviewImageHtml; ?>

                <div class="theme-right-column">

                    <div class="Buttons">
                        <div class="theme-buttons">
                            <?php
                            echo anchor(t('Apply'), 'dashboard/settings/mobilethemes/'.$ThemeName.'/'.$Session->TransientKey(), 'SmallButton EnableAddon EnableTheme', array('target' => '_top'));
                            //echo anchor(t('Preview'), 'dashboard/settings/previewtheme/'.$ThemeName, 'SmallButton PreviewAddon', array('target' => '_top'));
                            $this->EventArguments['ThemeInfo'] = $ThemeInfo;
                            $this->fireEvent('AfterThemeButtons');
                            ?>
                        </div>

                        <div class="theme-apply-progress"></div>

                        <div class="theme-applied">Enabled</div>
                    </div>

                    <?php echo $DescriptionHtml; ?>

                </div>



                <?php
                if ($this->data('EnabledTheme.Options')) {
                    $OptionsDescription = sprintf(t('This theme has additional options.', 'This theme has additional options on the %s page.'),
                        anchor(t('Mobile Theme Options'), '/dashboard/settings/mobilethemeoptions'));

                    echo '<div class="Options">',
                    $OptionsDescription,
                    '</div>';
                }
                ?>

                <?php if (is_array($RequiredApplications)): ?>

                    <dl>
                        <dt><?php echo t('Requires'); ?></dt>
                        <dd>

                            <?php
                            $i = 0;
                            foreach ($RequiredApplications as $RequiredApplication => $VersionInfo) {
                                if ($i > 0) {
                                    echo ', ';
                                }
                                printf(t('%1$s %2$s'), $RequiredApplication, $VersionInfo);
                                ++$i;
                            }
                            ?>
                    </dl>

                <?php endif; ?>

            </td>

            <?php
            if ($Col == 0) {
                echo '</tr>';
            }
            ?>

        <?php endforeach; ?>

        <?php
        if ($Col > 0) {
            echo '<td class="LastCol EmptyCol"'.($Col == 1 ? ' colspan="2"' : '').'>&#160;</td></tr>';
        }
        ?>

        </tbody>
    </table>

<?php endif; ?>
