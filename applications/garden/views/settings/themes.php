<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$AddonUrl = Gdn::Config('Garden.AddonUrl');
?>
<h1><?php echo T('Manage Themes'); ?></h1>
<?php
if ($AddonUrl != '')
   echo '<div class="FilterMenu">',
      Anchor('Get More Themes', $AddonUrl),
      '</div>';
         
?>
<div class="Info">
<?php
printf(
   T('ThemeHelp'),
   '<code>'.PATH_THEMES.'</code>'
);
?></div>
<div class="CurrentTheme">
   <h3><?php echo T('Current Theme'); ?></h3>
   <?php
   $Version = ArrayValue('Version', $this->EnabledTheme, '');
   $ThemeUrl = ArrayValue('Url', $this->EnabledTheme, '');
   $Author = ArrayValue('Author', $this->EnabledTheme, '');
   $AuthorUrl = ArrayValue('AuthorUrl', $this->EnabledTheme, '');   
   $NewVersion = ArrayValue('NewVersion', $this->EnabledTheme, '');
   $Upgrade = $NewVersion != '' && version_compare($NewVersion, $Version, '>');
   $PreviewImage = SafeGlob(PATH_THEMES . DS . $this->EnabledThemeFolder . DS . "screenshot{.gif,.jpg,.png}", GLOB_BRACE);
   $PreviewImage = count($PreviewImage) > 0 ? basename($PreviewImage[0]) : FALSE;
   if ($PreviewImage)
      echo Img('/themes/'.$this->EnabledThemeFolder.'/'.$PreviewImage, array('alt' => $this->EnabledThemeName, 'height' => '112', 'width' => '150'));
   
   echo '<h4>';
      echo $ThemeUrl != '' ? Url($this->EnabledThemeName, $ThemeUrl) : $this->EnabledThemeName;
      if ($Version != '')
         echo '<span class="Version">'.sprintf(T('version %s'), $Version).'</span>';
         
      if ($Author != '')
         echo '<span class="Author">'.sprintf('by %s', $AuthorUrl != '' ? Anchor($Author, $AuthorUrl) : $Author).'</span>';
   
   echo '</h4>';
   echo '<div class="Description">'.ArrayValue('Description', $this->EnabledTheme, '').'</div>';
   
   $RequiredApplications = ArrayValue('RequiredApplications', $this->EnabledTheme, FALSE);
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
            sprintf(T('%1$s version %2$s is available.'), $this->EnabledThemeName, $NewVersion),
            CombinePaths(array($AddonUrl, 'find', urlencode($this->EnabledThemeName)), '/')
         );
      echo '</div>';
   }
   ?>
</div>
<?php if (count($this->AvailableThemes) > 1) { ?>
<div class="BrowseThemes">
   <h3><?php echo T('Other Themes'); ?></h3>
   <table class="SelectionGrid Themes">
      <tbody>
   <?php
   $Alt = FALSE;
   $Cols = 3;
   $Col = 0;
   foreach ($this->AvailableThemes as $ThemeName => $ThemeInfo) {
      $ScreenName = ArrayValue('Name', $ThemeInfo, $ThemeName);
      $ThemeFolder = ArrayValue('Folder', $ThemeInfo, '');
      $Active = $ThemeFolder == $this->EnabledThemeFolder;
      if (!$Active) {
         $Version = ArrayValue('Version', $ThemeInfo, '');
         $ThemeUrl = ArrayValue('Url', $ThemeInfo, '');
         $Author = ArrayValue('Author', $ThemeInfo, '');
         $AuthorUrl = ArrayValue('AuthorUrl', $ThemeInfo, '');   
         $NewVersion = ArrayValue('NewVersion', $ThemeInfo, '');
         $Upgrade = $NewVersion != '' && version_compare($NewVersion, $Version, '>');
         $PreviewImage = SafeGlob(PATH_THEMES . DS . $ThemeFolder . DS . "screenshot{.gif,.jpg,.png}", GLOB_BRACE);
         $PreviewImage = count($PreviewImage) > 0 ? basename($PreviewImage[0]) : FALSE;
            
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
         $ColClass .= $PreviewImage ? ' HasPreview' : '';
         ?>
            <td class="<?php echo $ColClass; ?>">
               <?php
                  echo '<h4>';
                     echo $ThemeUrl != '' ? Url($ThemeName, $ThemeUrl) : $ThemeName;
                     if ($Version != '')
                        $Info = sprintf(T('Version %s'), $Version);
                        
                     if ($Author != '')
                        $Info .= sprintf('by %s', $AuthorUrl != '' ? Anchor($Author, $AuthorUrl) : $Author);
      
                  echo '</h4>';
                  
                  if ($PreviewImage) {
                     echo Anchor(Img('/themes/'.$ThemeFolder.'/'.$PreviewImage, array('alt' => $ThemeName, 'height' => '112', 'width' => '150')),
                        'garden/settings/previewtheme/'.$ThemeFolder,
                        '',
                        array('target' => '_top')
                     );
                  }

                  echo '<div class="Buttons">';
                  echo Anchor('Apply', 'garden/settings/themes/'.$ThemeFolder.'/'.$Session->TransientKey(), 'Button', array('target' => '_top'));
                  echo Anchor('Preview', 'garden/settings/previewtheme/'.$ThemeFolder, 'Button', array('target' => '_top'));
                  echo '</div>';

                  $Description = ArrayValue('Description', $ThemeInfo);
                  if ($Description)
                     echo '<em>'.$Description.'</em>';
                     
                  $RequiredApplications = ArrayValue('RequiredApplications', $ThemeInfo, FALSE);
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
      echo '<td class="LastCol EmptyCol"'.($Col == 1 ? ' colspan="2"' : '').'>&nbsp;</td></tr>';
   ?>
      </tbody>
   </table>
</div>
<?php
}

printf(T('AddonProblems'), '<code>'.PATH_CONF.DS.'config.php'.'</code>');