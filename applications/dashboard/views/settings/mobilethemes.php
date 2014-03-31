<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$AddonUrl = Gdn::Config('Garden.AddonUrl');
?>

<h1>
   <?php echo T('Manage Mobile Themes'); ?>
</h1>

<?php echo $this->Form->Errors(); ?>

<div class="Messages Errors TestAddonErrors Hidden">
   <ul>
      <li><?php echo T('The addon could not be enabled because it generated a fatal error: <pre>%s</pre>'); ?></li>
   </ul>
</div>


<?php /*
<div class="CurrentTheme">
   <?php
   $Version = $this->Data('EnabledTheme.Version');
   $ThemeUrl = $this->Data('EnabledTheme.Url');
   $Author = $this->Data('EnabledTheme.Author');
   $AuthorUrl = $this->Data('EnabledTheme.AuthorUrl');
   $NewVersion = $this->Data('EnabledTheme.NewVersion');
   $Upgrade = $NewVersion != '' && version_compare($NewVersion, $Version, '>');

   $PreviewUrl = $this->Data('EnabledTheme.ScreenshotUrl', FALSE);
   if ($PreviewUrl !== FALSE)
      echo Img($PreviewUrl, array('alt' => $this->Data('EnabledThemeName'), 'height' => '112', 'width' => '150'));

   echo '<h4>';
      echo $ThemeUrl != '' ? Anchor($this->Data('EnabledThemeName'), $ThemeUrl) : $this->Data('EnabledThemeName');
      if ($Version != '')
         echo '<span class="Version">'.sprintf(T('version %s'), $Version).'</span>';

      if ($Author != '')
         echo '<span class="Author">'.sprintf('by %s', $AuthorUrl != '' ? Anchor($Author, $AuthorUrl) : $Author).'</span>';

   echo '</h4>';
   echo '<div class="Description">'.GetValue('Description', $this->Data('EnabledTheme'), '').'</div>';
    if ($this->Data('EnabledTheme.Options')) {
      $OptionsDescription = sprintf(T('This theme has additional options.', 'This theme has additional options on the %s page.'),
         Anchor(T('Theme Options'), '/dashboard/settings/themeoptions'));

      echo '<div class="Options">',
         $OptionsDescription,
         '</div>';

   }

   $this->FireEvent('AfterCurrentTheme');

   $RequiredApplications = GetValue('RequiredApplications', $this->Data('EnabledTheme'), FALSE);
   if (is_array($RequiredApplications)) {
      echo '<div class="Requirements">'.T('Requires: ');

      $i = 0;
      if ($i > 0)
         echo ', ';

      foreach ($RequiredApplications as $RequiredApplication => $VersionInfo) {
         printf(T('%1$s Version %2$s'), $RequiredApplication, $VersionInfo);
         ++$i;
      }
      echo '</div>';
   }

   if ($Upgrade) {
      echo '<div class="Alert">';
      echo Url(
            sprintf(T('%1$s version %2$s is available.'), $this->Data('EnabledThemeName'), $NewVersion),
            CombinePaths(array($AddonUrl, 'find', urlencode($this->Data('EnabledThemeName'))), '/')
         );
      echo '</div>';
   }
   ?>
</div>

*/ ?>






<?php if (count($this->Data('AvailableThemes', array())) > 1): ?>





<div class="browser-mobile-themes">

   <?php

   // Get currently enabled theme data.
   $EnabledVersion = $this->Data('EnabledTheme.Version');
   $EnabledThemeUrl = $this->Data('EnabledTheme.Url');
   $EnabledAuthor = $this->Data('EnabledTheme.Author');
   $EnabledAuthorUrl = $this->Data('EnabledTheme.AuthorUrl');
   $EnabledNewVersion = $this->Data('EnabledTheme.NewVersion');
   $EnabledUpgrade = $EnabledNewVersion != '' && version_compare($EnabledNewVersion, $EnabledVersion, '>');
   $EnabledPreviewUrl = $this->Data('EnabledTheme.ScreenshotUrl', FALSE);
   $EnabledThemeName = $this->Data('EnabledThemeName');

   if ($this->Data('EnabledTheme.Options')) {
      $OptionsDescription = sprintf(T('This theme has additional options.', 'This theme has additional options on the %s page.'), Anchor(T('Theme Options'), '/dashboard/settings/themeoptions'));
   }

   foreach ($this->Data('AvailableThemes') as $ThemeName => $ThemeInfo):

      decho($EnabledThemeName);
   decho($ThemeName);

      if ($EnabledThemeName == $ThemeName) {
         decho('THIS IS IT');
      }

      $ScreenName = GetValue('Name', $ThemeInfo, $ThemeName);
      $ThemeFolder = GetValue('Folder', $ThemeInfo, '');
      $Active = $ThemeFolder == $this->Data('EnabledThemeFolder');

      decho($ScreenName);

      $Version = GetValue('Version', $ThemeInfo, '');
      $ThemeUrl = GetValue('Url', $ThemeInfo, '');
      $Author = GetValue('Author', $ThemeInfo, '');
      $AuthorUrl = GetValue('AuthorUrl', $ThemeInfo, '');
      $NewVersion = GetValue('NewVersion', $ThemeInfo, '');
      $Upgrade = $NewVersion != '' && version_compare($NewVersion, $Version, '>');
      $PreviewUrl = GetValue('ScreenshotUrl', $ThemeInfo, FALSE);

      ?>
            <div class="themeblock">
               <?php
                  echo '<h4>';
                     echo $ThemeUrl != '' ? Anchor($ScreenName, $ThemeUrl) : $ScreenName;
                  echo '</h4>';

                  if ($PreviewUrl !== FALSE) {
                     echo Anchor(Img($PreviewUrl, array('alt' => $ScreenName, 'height' => '112', 'width' => '150')),
                        'dashboard/settings/previewtheme/'.$ThemeName,
                        '',
                        array('target' => '_top')
                     );
                  }

                  echo '<div class="Buttons">';
                  echo Anchor(T('Apply'), 'dashboard/settings/themes/'.$ThemeName.'/'.$Session->TransientKey(), 'SmallButton EnableAddon EnableTheme', array('target' => '_top'));
                  echo Anchor(T('Preview'), 'dashboard/settings/previewtheme/'.$ThemeName, 'SmallButton PreviewAddon', array('target' => '_top'));
                        $this->EventArguments['ThemeInfo'] = $ThemeInfo;
                        $this->FireEvent('AfterThemeButtons');
                  echo '</div>';

                  $Description = GetValue('Description', $ThemeInfo);
                  if ($Description)
                     echo '<em>'.$Description.'</em>';

                  $RequiredApplications = GetValue('RequiredApplications', $ThemeInfo, FALSE);
                  if (is_array($RequiredApplications)) {
                     echo '<dl>
                        <dt>'.T('Requires').'</dt>
                        <dd>';

                     $i = 0;
                     foreach ($RequiredApplications as $RequiredApplication => $VersionInfo) {
                        if ($i > 0)
                           echo ', ';

                        printf(T('%1$s %2$s'), $RequiredApplication, $VersionInfo);
                        ++$i;
                     }
                     echo '</dl>';
                  }

                  if ($Upgrade) {
                     echo '<div class="Alert">';
                     echo Anchor(
                           sprintf(T('%1$s version %2$s is available.'), $ScreenName, $NewVersion),
                           CombinePaths(array($AddonUrl, 'find', urlencode($ThemeName)), '/')
                        );
                     echo '</div>';
                  }
               ?>
            </div>
   <?php endforeach; ?>
</div>

<?php endif; ?>
