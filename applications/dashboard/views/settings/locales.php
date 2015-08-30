<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
?>
<div class="Help Aside">
    <?php
    echo '<h2>', t('Need More Help?'), '</h2>';
    echo '<ul>';
    echo '<li>', anchor(t('Internationalization & Localization'), 'http://docs.vanillaforums.com/developers/locales/'), '</li>';
    echo '</ul>';
    ?>
</div>

<h1><?php echo $this->data('Title'); ?></h1>
<div class="Info">
    <?php
    printf(
        t('Locales allow you to support other languages on your site.', "Locales allow you to support other languages on your site. Enable and disable locales you want to make available here."),
        '<code>'.PATH_ROOT.'/locales</code>'
    );

    if ($this->data('DefaultLocaleWarning'))
        echo '<div class="Errors">', sprintf(t('Your default locale won\'t display properly', 'Your default locale won\'t display properly until it is enabled below. Please enable the following: %s.'), $this->data('MatchingLocalePacks')), '</div>';

    echo
    '<p>',
    $this->Form->open(),
    $this->Form->errors(),
    '<b>', t('Default Locale'), '</b> ',
    $this->Form->DropDown('Locale', $this->data('Locales')),
    $this->Form->button('Save', array('style' => 'margin-bottom: 0px')),
    ' ', $this->Form->close(),
    '</p>';
    ?>
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
        <th><?php echo t('Locale Name'); ?></th>
        <th><?php echo t('Description'); ?></th>
    </tr>
    </thead>
    <tbody>
    <?php
    $Alt = FALSE;
    foreach ($this->data('AvailableLocales') as $Key => $Info) {
        // Hide skeleton locale pack
        if ($Key == 'skeleton')
            continue;

        $ToggleText = $this->data("EnabledLocales.$Key") ? 'Disable' : 'Enable';
        $RowClass = $this->data("EnabledLocales.$Key") ? 'Enabled' : 'Disabled';
        if ($Alt) $RowClass .= ' Alt';
        ?>
        <tr class="More <?php echo $RowClass; ?>">
            <th><?php echo val('Name', $Info, $Key); ?></th>
            <td class="Alt"><?php echo val('Description', $Info, ''); ?></td>
        </tr>
        <tr class="<?php echo $RowClass; ?>">
            <td class="Info"><?php
                echo anchor(
                    t($ToggleText),
                    '/settings/locales/'.strtolower($ToggleText).'/'.urlencode($Key).'/'.$Session->TransientKey(),
                    $ToggleText.'Addon SmallButton'
                );
                ?></td>
            <td class="Alt Info"><?php
                $RequiredApplications = val('RequiredApplications', $Info, false);
                $RequiredPlugins = val('RequiredPlugins', $Info, false);

                $InfoItems = arrayTranslate($Info, array('Locale' => t('Locale'), 'Version' => t('Version')));
                $InfoString = ImplodeAssoc(': ', '<span>|</span>', $InfoItems);

                //            if (is_array($RequiredApplications) || is_array($RequiredPlugins)) {
                //               if ($Info != '')
                //                  $Info .= '<span>|</span>';
                //
                //               $Info .= t('Requires: ');
                //            }

                //            $i = 0;
                //            if (is_array($RequiredApplications)) {
                //               if ($i > 0)
                //                  $Info .= ', ';
                //
                //               foreach ($RequiredApplications as $RequiredApplication => $VersionInfo) {
                //                  $Info .= sprintf(t('%1$s Version %2$s'), $RequiredApplication, $VersionInfo);
                //                  ++$i;
                //               }
                //            }
                //
                //            if ($RequiredPlugins !== FALSE) {
                //               foreach ($RequiredPlugins as $RequiredPlugin => $VersionInfo) {
                //                  if ($i > 0)
                //                     $Info .= ', ';
                //
                //                  $Info .= sprintf(t('%1$s Version %2$s'), $RequiredPlugin, $VersionInfo);
                //                  ++$i;
                //               }
                //            }

                if ($Author = val('Author', $Info)) {
                    $InfoString .= '<span>|</span>';
                    $InfoString .= sprintf('By %s', anchor($Author, val('AuthorUrl', $Info, '')));
                }

                echo $InfoString != '' ? $InfoString : '&#160;';

                ?></td>
        </tr>
    <?php
    }
    ?>
    </tbody>
</table>
