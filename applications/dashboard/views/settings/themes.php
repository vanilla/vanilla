<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
$AddonUrl = Gdn::config('Garden.AddonUrl');
$themePlaceholder = 'applications/dashboard/design/images/theme-placeholder.svg';
$themeSpacer = 'applications/dashboard/design/images/theme-spacer.png';

$links = '<ul>';
$links .= wrap(anchor(t("Video tutorial on managing appearance"), 'settings/tutorials/appearance'), 'li');
$links .= wrap(anchor(t('Addons Overview'), 'https://docs.vanillaforums.com/developer/addons/'), 'li');
$links .= wrap(anchor(t('Quick-Start Guide to Creating Themes for Vanilla'), 'https://docs.vanillaforums.com/developer/addons/theme-quickstart/'), 'li');
$links .= '</ul>';

helpAsset(sprintf(t('About %s'), t('Themes')), sprintf(t('ThemeHelp'), '<code style="word-wrap: break-word;">'.PATH_THEMES.'</code>').'<br/><br/>'.anchor(t('Theming Documentation'), 'http://docs.vanillaforums.com/developer/theming/', '', ["target" => "_blank"]));
helpAsset(t('About Theme Preview'), t('Not getting what you expect when you preview your theme?').' '
    .t('Theme preview is limited to displaying the theme\'s template and css.').' '
    .t('Overridden views or themehooks can have unintended side effects and are not previewed.'));
helpAsset(t('Need More Help?'), $links);

?>
<div class="header-menu">
    <a class="header-menu-item active" role="heading" aria-level="1" href="<?php echo url('/dashboard/settings/themes'); ?>"><?php echo t('Desktop Themes'); ?></a>
    <a class="header-menu-item" href="<?php echo url('/dashboard/settings/mobilethemes'); ?>"><?php echo t('Mobile Themes'); ?></a>
</div>
<?php echo $this->Form->errors(); ?>
    <div class="Messages Errors TestAddonErrors Hidden">
        <ul>
            <li><?php echo t('The addon could not be enabled because it generated a fatal error: <pre>%s</pre>'); ?></li>
        </ul>
    </div>
<?php
if ($currentTheme = $this->data('CurrentTheme')) {
    echo $currentTheme;
}
?>
<?php if (count($this->data('AvailableThemes', [])) > 1) { ?>
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
                $allowPreview = val('AllowPreview', $ThemeInfo, true);
                $Upgrade = $NewVersion != '' && version_compare($NewVersion, $Version, '>');
                $PreviewUrl = val('IconUrl', $ThemeInfo, false);
                $class = $Active ? ' Enabled' : '';
                ?>
                <li class="<?php echo $class; ?> label-selector-item">
                    <div class="theme-wrap">
                        <div class="theme-spacer">
                            <?php echo img($themeSpacer, ['alt' => $ScreenName, 'class' => 'label-selector-image']); ?>
                        </div>
                        <div class="image-wrap">
                            <?php if ($PreviewUrl !== FALSE) {
                                echo img($PreviewUrl, ['alt' => $ScreenName, 'class' => 'label-selector-image']);
                            } else {
                                echo img($themePlaceholder, ['alt' => $ScreenName, 'class' => 'label-selector-image']);
                            } ?>
                            <div class="overlay">
                                <div class="label-selector-corner-link">
                                    <?php echo anchor(dashboardSymbol('expand', 'icon-16'), 'dashboard/settings/themeinfo/'.$ThemeName, 'js-modal', ['data-css-class' => 'modal-center modal-md', 'data-title' => $ScreenName, 'data-modal-type' => 'noheader']); ?>
                                </div>
                                <div class="buttons">
                                    <?php echo anchor(t('Apply'), 'dashboard/settings/themes/'.$ThemeName.'/'.$Session->transientKey(), 'btn btn-overlay EnableAddon EnableTheme', ['target' => '_top']);
                                    if ($allowPreview) {
                                        echo anchor(t('Preview'), 'dashboard/settings/previewtheme/'.$ThemeName, 'btn btn-overlay js-preview-addon');
                                    }
                                    $this->EventArguments['ThemeInfo'] = $ThemeInfo;
                                    $this->fireEvent('AfterThemeButtons'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="title">
                        <?php if ($ThemeUrl != '') {
                            echo $ScreenName.' '.anchor(dashboardSymbol('external-link', 'icon-text'), $ThemeUrl, '', ['title' => t('Theme website')]);
                        } else {
                            echo $ScreenName;
                        } ?>
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
                                    combinePaths([$AddonUrl, 'find', urlencode($ThemeName)], '/')
                                ); ?>
                            </div>';
                        <?php } ?>
                    </div>
                </li>
            <?php } ?>
        </ul>
    </div>
<?php }
