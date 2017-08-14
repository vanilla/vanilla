<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
$AddonUrl = Gdn::config('Garden.AddonUrl');

$desc = t('Mobile themes allow you to change the look and feel of your site on smaller devices.').' ';
$desc .= t('They work just like regular themes. Once one has been added to the themes folder, you can enable it here.');

helpAsset(sprintf(t('About %s'), t('Mobile Themes')), $desc);
helpAsset(t('About Theme Preview'), t('Not getting what you expect when you preview your theme?').' '
    .t('Theme preview is limited to displaying the theme\'s template and css.').' '
    .t('Overridden views or themehooks can have unintended side effects and are not previewed.'));

?>
<svg display="none">
    <symbol viewBox="0 0 252 281" id="mobile-frame"><g fill-rule="evenodd"><path d="M3.15,15.7582284 C3.15,8.79489615 8.78765609,3.15 15.7571747,3.15 L236.242825,3.15 C243.205576,3.15 248.85,8.78126062 248.85,15.7582284 L248.85,280.35 L3.15,280.35 L3.15,15.7582284 L3.15,15.7582284 L3.15,15.7582284 Z M243.6,279.201172 L243.6,15.7582284 C243.6,11.6862452 240.311573,8.4 236.242825,8.4 L15.7571747,8.4 C11.6900229,8.4 8.4,11.6915185 8.4,15.7582284 L8.60057618,279.102293 L243.6,279.201172 Z M1.13686838e-13,67.7172107 C1.13686838e-13,66.8516641 0.699087095,66.15 1.575,66.15 L3.15,66.15 L3.15,151.2 L1.575,151.2 C0.705151519,151.2 1.13686838e-13,150.490284 1.13686838e-13,149.632789 L1.13686838e-13,67.7172107 L1.13686838e-13,67.7172107 Z M252,67.7263729 C252,66.8557662 251.300913,66.15 250.425,66.15 L248.85,66.15 L248.85,91.35 L250.425,91.35 C251.294848,91.35 252,90.6497064 252,89.7736271 L252,67.7263729 L252,67.7263729 Z M252,99.2263729 C252,98.3557662 251.300913,97.65 250.425,97.65 L248.85,97.65 L248.85,122.85 L250.425,122.85 C251.294848,122.85 252,122.149706 252,121.273627 L252,99.2263729 L252,99.2263729 Z M186.376373,-8.06113616e-15 C185.505766,-7.90120831e-15 184.8,0.699087095 184.8,1.575 L184.8,3.15 L210,3.15 L210,1.575 C210,0.705151519 209.299706,-1.22720842e-14 208.423627,-1.21111511e-14 L186.376373,-8.06113616e-15 L186.376373,-8.06113616e-15 Z M105,23.4015945 C105,22.2961444 105.89666,21.4 106.997492,21.4 L145.002508,21.4 C146.105692,21.4 147,22.290712 147,23.4015945 L147,23.5984055 C147,24.7038556 146.10334,25.6 145.002508,25.6 L106.997492,25.6 C105.894308,25.6 105,24.709288 105,23.5984055 L105,23.4015945 L105,23.4015945 Z"/></g></symbol>
</svg>
<div class="header-menu">
    <a class="header-menu-item" href="<?php echo url('/dashboard/settings/themes'); ?>"><?php echo t('Desktop Themes'); ?></a>
    <a class="header-menu-item active" role="heading" aria-level="1" href="<?php echo url('/dashboard/settings/mobilethemes'); ?>"><?php echo t('Mobile Themes'); ?></a>
</div>
<?php echo $this->Form->errors(); ?>

<div class="Messages Errors TestAddonErrors Hidden">
    <ul>
        <li><?php echo t('The addon could not be enabled because it generated a fatal error: <pre>%s</pre>'); ?></li>
    </ul>
</div>
<div class="media media-callout media-callout-grey-bg current-theme-mobile CurrentTheme">
    <?php
    $PreviewUrl = $this->data('EnabledTheme.MobileScreenshotUrl', false); ?>
    <div class="media-left">
        <div class="mobile-theme-wrap">
            <div class="mobile-frame">
                <svg class="icon icon-mobile-frame" viewBox="0 0 252 281"><use xlink:href="#mobile-frame" /></svg>
            </div>
            <div class="image-wrap">
                <?php
                if ($PreviewUrl !== FALSE) {
                    echo img($PreviewUrl, ['alt' => $this->data('EnabledThemeName')]);
                } else {
                    echo img('/themes/mobile/mobile.png', ['alt' => $ScreenName]);
                } ?>
            </div>
        </div>
    </div>

    <div class="media-body">
        <div class="flag"><?php echo t('Current Mobile Theme'); ?></div>
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

        $this->EventArguments['IsMobile'] = true;
        $this->fireEvent('AfterCurrentMobileTheme');

        if ($Upgrade) {
            echo '<div class="Alert alert">';
            echo url(
                sprintf(t('%1$s version %2$s is available.'), $this->data('EnabledThemeName'), $NewVersion),
                combinePaths([$AddonUrl, 'find', urlencode($this->data('EnabledThemeName'))], '/')
            );
            echo '</div>';
        }
        ?>
        </div>
    </div>
</div>
<?php if (count($this->data('AvailableThemes', []))): ?>

    <ul class="browser-mobile-themes label-selector">
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
            $allowPreview = val('AllowPreview', $ThemeInfo, true);
            $RequiredApplications = val('RequiredApplications', $ThemeInfo, false);

            $ClassCurrentTheme = ($EnabledThemeInfo['Index'] == $ThemeInfo['Index'])
                ? 'active'
                : '';



            $PreviewImageHtml = ($PreviewUrl !== FALSE)
                ? anchor(img($PreviewUrl, ['alt' => $ScreenName, 'class' => 'label-selector-image']), $PreviewUrl, '', ['class' => 'theme-image mfp-image'])
                : anchor(img('/themes/mobile/mobile.png', ['alt' => $ScreenName, 'class' => 'label-selector-image']), $PreviewUrl, '', ['class' => 'theme-image mfp-image']);

            $DescriptionHtml = ($Description)
                ? '<em class="theme-description">'.$Description.'</em>'
                : '';


            ?>

            <li class="label-selector-item themeblock <?php echo $ClassCurrentTheme; ?>">

                <!--<div class="author-name">
               <?php echo $Author; ?>
            </div>-->
                <div class="mobile-theme-wrap">
                    <div class="mobile-frame">
                        <svg class="icon icon-mobile-frame" viewBox="0 0 252 281"><use xlink:href="#mobile-frame" /></svg>
                    </div>
                    <div class="image-wrap">
                    <?php echo $PreviewImageHtml; ?>
                        <div class="overlay">
                            <div class="label-selector-corner-link">
                                <?php echo anchor(dashboardSymbol('expand', 'icon-16'), 'dashboard/settings/themeinfo/'.$ThemeName, 'js-modal', ['data-css-class' => 'modal-center modal-md', 'data-modal-type' => 'noheader']); ?>
                            </div>
                            <div class="buttons">
                                <?php echo anchor(t('Apply'), 'dashboard/settings/mobilethemes/'.$ThemeName.'/'.$Session->transientKey(), 'EnableAddon EnableTheme btn btn-overlay', ['target' => '_top']);
                                if ($allowPreview) {
                                    echo anchor(t('Preview'), 'dashboard/settings/previewtheme/'.$ThemeName, 'btn btn-overlay js-preview-addon');
                                }
                                $this->EventArguments['ThemeInfo'] = $ThemeInfo;
                                $this->fireEvent('AfterThemeButtons');
                                ?>
                            </div>
                            <div class="selected">
                                <?php echo dashboardSymbol('checkmark'); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="title">
                    <?php echo ($ThemeUrl != '') ? anchor($ScreenName, $ThemeUrl) : $ScreenName; ?>
                </div>
                <div class="description">
                    <?php echo $DescriptionHtml; ?>

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
                </div>

            </li>

        <?php endforeach; ?>

<?php endif; ?>
