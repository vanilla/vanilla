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
//if ($AddonUrl != '') {
//    echo anchor(t('Get More Themes').' <span class="icon icon-external-link"></span>', $AddonUrl, 'btn btn-primary');
//}
?>
<?php echo $this->Form->errors(); ?>
    <div class="Messages Errors TestAddonErrors Hidden">
        <ul>
            <li><?php echo t('The addon could not be enabled because it generated a fatal error: <pre>%s</pre>'); ?></li>
        </ul>
    </div>
    <div class="media media-callout CurrentTheme">
        <?php
        $PreviewUrl = $this->data('EnabledTheme.IconUrl', false);
        echo '<div class="media-left grid-item">';
        echo '<div class="image-wrap grid-image-wrap">';
        if ($PreviewUrl !== FALSE) {
            echo img($PreviewUrl, array('alt' => $this->data('EnabledThemeName'), 'class' => 'grid-image'));
        } else {
            echo img($themePlaceholder, array('alt' => $ScreenName, 'class' => 'grid-image'));
        }
        echo '</div>';
        echo '</div>'; ?>

        <div class="media-body">
            <div class="flag"><?php echo t('Current Theme'); ?></div>
            <?php
            $Version = $this->data('EnabledTheme.Version');
            $ThemeUrl = $this->data('EnabledTheme.Url');
            $Author = $this->data('EnabledTheme.Author');
            $AuthorUrl = $this->data('EnabledTheme.AuthorUrl');
            $NewVersion = $this->data('EnabledTheme.NewVersion');
            $Upgrade = $NewVersion != '' && version_compare($NewVersion, $Version, '>');

            echo '<div class="media-title">';
            echo '<h3 class="media-heading theme-name">';
            echo $ThemeUrl != '' ? anchor($this->data('EnabledThemeName'), $ThemeUrl) : $this->data('EnabledThemeName');
            echo '</h3>';
            echo '<div class="info">';

            $info = [];
            if ($Author != '') {
                $info[] = '<span class="Author">'.sprintf('Created by %s', $AuthorUrl != '' ? anchor($Author, $AuthorUrl) : $Author).'</span>';
            }

            if ($Version != '') {
                $info[] = '<span class="Version">'.sprintf(t('Version %s'), $Version).'</span>';
            }

            $RequiredApplications = val('RequiredApplications', $this->data('EnabledTheme'), false);
            $required = '';
            if (is_array($RequiredApplications)) {
                $required .= '<div class="Requirements">'.t('Requires: ');

                $i = 0;
                if ($i > 0)
                    $required .= ', ';

                foreach ($RequiredApplications as $RequiredApplication => $VersionInfo) {
                    $required .= printf(t('%1$s Version %2$s'), $RequiredApplication, $VersionInfo);
                    ++$i;
                }
                $required .= '</div>';
            }

            if ($required) {
                $info[] = $required;
            }

            echo implode('<span class="spacer">|</span>', $info);
            echo '</div>';
            echo '</div>';
            echo '<div class="media-description Description"><div class="description">'.$this->data('EnabledTheme.Description', '').'</div>';

            if ($this->data('EnabledTheme.Options')) {
                $OptionsDescription = sprintf(t('This theme has additional options.', 'This theme has additional options on the %s page.'),
                    anchor(t('Theme Options'), '/dashboard/settings/themeoptions'));

                echo '<div class="Options">',
                $OptionsDescription,
                '</div>';

            }

            $this->fireEvent('AfterCurrentTheme');

            if ($Upgrade) {
                echo '<div class="Alert alert">';
                echo url(
                    sprintf(t('%1$s version %2$s is available.'), $this->data('EnabledThemeName'), $NewVersion),
                    CombinePaths(array($AddonUrl, 'find', urlencode($this->data('EnabledThemeName'))), '/')
                );
                echo '</div>';
            }
            ?>
        </div>
    </div>
    </div>
<?php if (count($this->data('AvailableThemes', array())) > 1) { ?>
    <div class="BrowseThemes js-themes">
        <ul class="label-selector">
            <?php
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
                    $PreviewUrl = val('IconUrl', $ThemeInfo, false);

                    $class = $Active ? ' Enabled' : '';
                    $class .= $PreviewUrl ? ' HasPreview' : '';
                    ?>
                    <li class="<?php echo $class; ?> label-selector-item">
                        <div class="theme-wrap">
                            <div class="theme-spacer">
                                <?php echo img($themeSpacer, array('alt' => $ScreenName, 'class' => 'label-selector-image')); ?>
                            </div>
                            <?php
                            echo '<div class="image-wrap">';
                            if ($PreviewUrl !== FALSE) {
                                echo Img($PreviewUrl, array('alt' => $ScreenName, 'class' => 'label-selector-image'));
                            } else {
                                echo img($themePlaceholder, array('alt' => $ScreenName, 'class' => 'label-selector-image'));
                            }
                            echo '<div class="overlay">';
                            echo '<div class="buttons">';
                            echo anchor(t('Apply'), 'dashboard/settings/themes/'.$ThemeName.'/'.$Session->TransientKey(), 'btn btn-overlay EnableAddon EnableTheme', array('target' => '_top'));
                            //                        echo anchor(t('Preview'), 'dashboard/settings/previewtheme/'.$ThemeName, 'btn btn-overlay PreviewAddon', array('target' => '_top'));
                            $this->EventArguments['ThemeInfo'] = $ThemeInfo;
                            $this->fireEvent('AfterThemeButtons');
                            echo '</div>';
                            echo '</div>';
                            echo '</div>';
                            echo '</div>';
                            echo '<div class="title">';
                            echo $ThemeUrl != '' ? anchor($ScreenName, $ThemeUrl) : $ScreenName;
                            echo '</div>';


                            echo '<div class="description">';
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

                            if ($Upgrade) {
                                echo '<div class="Alert">';
                                echo anchor(
                                    sprintf(t('%1$s version %2$s is available.'), $ScreenName, $NewVersion),
                                    CombinePaths(array($AddonUrl, 'find', urlencode($ThemeName)), '/')
                                );
                                echo '</div>';
                            }
                            echo '</div>';
                            ?>
                    </li>
                    <?php
                }
            }
            ?>
        </ul>
    </div>
    <?php
}
