<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
$AddonUrl = Gdn::config('Garden.AddonUrl');
$themePlaceholder = asset('applications/dashboard/design/images/theme-placeholder.svg');
$themeSpacer = asset('applications/dashboard/design/images/theme-spacer.png');
?>
<?php Gdn_Theme::assetBegin('Help'); ?>
    <div class="Help Aside">
        <?php echo '<h2>', t('About Themes'), '</h2>'; ?>
        <?php echo sprintf(t('ThemeHelp'), '<code style="word-wrap: break-word;">'.PATH_THEMES.'</code>'); ?>
        <?php
        echo '<h2>', t('Need More Help?'), '</h2>';
        echo '<ul>';
        echo wrap(Anchor(t("Video tutorial on managing appearance"), 'settings/tutorials/appearance'), 'li');
        echo wrap(Anchor(t('Theming Overview'), 'http://docs.vanillaforums.com/theming/'), 'li');
        echo wrap(Anchor(t('Quick-Start Guide to Creating Themes for Vanilla'), 'http://docs.vanillaforums.com/theming/quickstart/'), 'li');
        echo '</ul>';
        ?>
    </div>
<?php Gdn_Theme::assetEnd(); ?>
    <div class="header-menu">
        <a href="<?php echo url('/dashboard/settings/themes'); ?>" class="active"><?php echo t('Desktop Themes'); ?></a>
        <a href="<?php echo url('/dashboard/settings/mobilethemes'); ?>"><?php echo t('Mobile Themes'); ?></a>
    </div>
<?php
if ($currentTheme = $this->Data('CurrentTheme')) {
    echo $currentTheme;
}
?>
<?php echo $this->Form->errors(); ?>
    <div class="Messages Errors TestAddonErrors Hidden">
        <ul>
            <li><?php echo t('The addon could not be enabled because it generated a fatal error: <pre>%s</pre>'); ?></li>
        </ul>
    </div>
<?php if (count($this->data('AvailableThemes', array())) > 1) { ?>
    <div class="BrowseThemes js-themes">
        <ul class="label-selector">
            <?php
            foreach ($this->data('AvailableThemes') as $ThemeName => $ThemeInfo) {
                $ScreenName = val('Name', $ThemeInfo, $ThemeName);
                $ThemeFolder = val('Folder', $ThemeInfo, '');
                $Active = $ThemeFolder == $this->data('EnabledThemeFolder');
                if ($Active) {
                    continue;
                }
                $Version = val('Version', $ThemeInfo, '');
                $ThemeUrl = val('Url', $ThemeInfo, '');
                $Author = val('Author', $ThemeInfo, '');
                $AuthorUrl = val('AuthorUrl', $ThemeInfo, '');
                $NewVersion = val('NewVersion', $ThemeInfo, '');
                $Upgrade = $NewVersion != '' && version_compare($NewVersion, $Version, '>');
                $PreviewUrl = val('IconUrl', $ThemeInfo, false);

                $class = $Active ? ' Enabled' : '';
                $class .= $PreviewUrl ? ' HasPreview' : '';
                ?>
                <li class="<?php echo $class; ?> label-selector-item">
                    <div class="theme-wrap">
                        <div class="theme-spacer">
                            <?php echo img($themeSpacer, array('alt' => $ScreenName, 'class' => 'label-selector-image')); ?>
                        </div>
                        <div class="image-wrap">
                            <?php if ($PreviewUrl !== FALSE) {
                                echo Img($PreviewUrl, array('alt' => $ScreenName, 'class' => 'label-selector-image'));
                            } else {
                                echo img($themePlaceholder, array('alt' => $ScreenName, 'class' => 'label-selector-image'));
                            } ?>
                            <div class="overlay">
                                <div class="buttons">
                                    <?php echo anchor(t('Apply'), 'dashboard/settings/themes/'.$ThemeName.'/'.$Session->TransientKey(), 'btn btn-overlay EnableAddon EnableTheme', array('target' => '_top'));
                                    //                        echo anchor(t('Preview'), 'dashboard/settings/previewtheme/'.$ThemeName, 'btn btn-overlay PreviewAddon', array('target' => '_top'));
                                    echo anchor(dashboardSymbol('external-link'), 'dashboard/settings/themeinfo/'.$ThemeName, 'js-modal btn btn-icon');
                                    $this->EventArguments['ThemeInfo'] = $ThemeInfo;
                                    $this->fireEvent('AfterThemeButtons'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="title">
                        <?php echo $ThemeUrl != '' ? anchor($ScreenName, $ThemeUrl) : $ScreenName; ?>
                    </div>


                    <div class="description">
                        <?php
                        $Description = val('Description', $ThemeInfo);
                        if ($Description) {
                            echo '<div class="theme-description">'.$Description.'</div>';
                        }

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

                        if ($Upgrade) { ?>
                            <div class="Alert">
                                <?php echo anchor(
                                    sprintf(t('%1$s version %2$s is available.'), $ScreenName, $NewVersion),
                                    CombinePaths(array($AddonUrl, 'find', urlencode($ThemeName)), '/')
                                ); ?>
                            </div>';
                        <?php } ?>
                    </div>
                </li>
            <?php } ?>
        </ul>
    </div>
<?php }
