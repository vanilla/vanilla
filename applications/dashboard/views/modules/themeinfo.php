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
