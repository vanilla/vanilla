<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$AddonUrl = Gdn::Config('Garden.AddonUrl');
?>
<div class="Help Aside">
   <?php
   echo '<h2>', T('Need More Help?'), '</h2>';
   echo '<ul>';
   echo Wrap(Anchor(T("Video tutorial on managing appearance"), 'settings/tutorials/appearance'), 'li');
   echo Wrap(Anchor(T('Theming Overview'), 'http://vanillaforums.org/page/Configuration-DashboardAppearanceThemes'), 'li');
   echo Wrap(Anchor(T('Quick-Start Guide to Creating Themes for Vanilla'), 'http://vanillaforums.org/page/ThemeQuickStart'), 'li');
   echo '</ul>';
   ?>
</div>
<h1><?php echo T('Manage Themes'); ?></h1>
<div class="Info">
<?php
printf(
   T('ThemeHelp'),
   '<code>'.PATH_THEMES.'</code>'
);
?></div>
<?php
if ($AddonUrl != '')
   echo '<div class="FilterMenu">',
      Anchor(T('Get More Themes'), $AddonUrl, 'SmallButton'),
      '</div>';
         
?>
<?php echo $this->Form->Errors(); ?>
<div class="Messages Errors TestAddonErrors Hidden">
   <ul>
      <li><?php echo T('The addon could not be enabled because it generated a fatal error: <pre>%s</pre>'); ?></li>
   </ul>
</div>
<div class="CurrentTheme">
   <h3><?php echo T('Current Theme'); ?></h3>
   <?php
   $Version = $this->Data('EnabledTheme.Version');
   $ThemeUrl = $this->Data('EnabledTheme.Url');
   $Author = $this->Data('EnabledTheme.Author');
   $AuthorUrl = $this->Data('EnabledTheme.AuthorUrl');
   $NewVersion = $this->Data('EnabledTheme.NewVersion');
   $Upgrade = $NewVersion != '' && version_compare($NewVersion, $Version, '>');
   
   $PreviewUrl = $this->Data('EnabledTheme.ScreenshotUrl', FALSE);
   if ($PreviewUrl !== FALSE)
      echo Img($PreviewUrl, array('alt' => $this->Data('EnabledThemeName')));
   
   echo '<h4>';
      echo $ThemeUrl != '' ? Url($this->Data('EnabledThemeName'), $ThemeUrl) : $this->Data('EnabledThemeName');
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
<?php if (count($this->Data('AvailableThemes', array())) > 1) { ?>
<div class="BrowseThemes">
   <h3><?php echo T('Other Themes'); ?></h3>
   <table class="SelectionGrid Themes">
      <tbody>
   <?php
   $Alt = FALSE;
   $Cols = 3;
   $Col = 0;
   
   foreach ($this->Data('AvailableThemes') as $ThemeName => $ThemeInfo) {
      $ScreenName = GetValue('Name', $ThemeInfo, $ThemeName);
      $ThemeFolder = GetValue('Folder', $ThemeInfo, '');
      $Active = $ThemeFolder == $this->Data('EnabledThemeFolder');
      if (!$Active) {
         $Version = GetValue('Version', $ThemeInfo, '');
         $ThemeUrl = GetValue('Url', $ThemeInfo, '');
         $Author = GetValue('Author', $ThemeInfo, '');
         $AuthorUrl = GetValue('AuthorUrl', $ThemeInfo, '');   
         $NewVersion = GetValue('NewVersion', $ThemeInfo, '');
         $Upgrade = $NewVersion != '' && version_compare($NewVersion, $Version, '>');
         $PreviewUrl = GetValue('ScreenshotUrl', $ThemeInfo, FALSE);
         
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
                     echo $ThemeUrl != '' ? Url($ScreenName, $ThemeUrl) : $ScreenName;
							/*
                     if ($Version != '')
                        $Info = sprintf(T('Version %s'), $Version);
                        
                     if ($Author != '')
                        $Info .= sprintf('by %s', $AuthorUrl != '' ? Anchor($Author, $AuthorUrl) : $Author);
							*/
                  echo '</h4>';
                  
                  if ($PreviewUrl !== FALSE) {
                     echo Anchor(Img($PreviewUrl, array('alt' => $ScreenName)),
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
