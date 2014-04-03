<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$AddonUrl = Gdn::Config('Garden.AddonUrl');
?>

<h1>
   <?php echo T('Manage Mobile Themes'); ?>
</h1>

<div class="Info">
   <p class="P"><?php echo T('Mobile themes allow you to change the look and feel of your site on smaller devices.'); ?></p>
   <p class="P"><?php echo T('They work just like regular themes. Once one has been added to the themes folder, you can enable it here.'); ?></p>
</div>

<h3>Available Mobile Themes</h3>

<?php echo $this->Form->Errors(); ?>

<div class="Messages Errors TestAddonErrors Hidden">
   <ul>
      <li><?php echo T('The addon could not be enabled because it generated a fatal error: <pre>%s</pre>'); ?></li>
   </ul>
</div>

<?php if (count($this->Data('AvailableThemes', array()))): ?>

   <table class="browser-mobile-themes SelectionGrid Themes">
      <tbody>

      <?php
         // Get currently enabled theme data.
         $EnabledThemeInfo = $this->Data('EnabledThemeInfo');
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

         $Cols = 3;
         $Col = 0;
         foreach ($this->Data('AvailableThemes') as $ThemeName => $ThemeInfo):

            $ScreenName = GetValue('Name', $ThemeInfo, $ThemeName);
            $ThemeFolder = GetValue('Folder', $ThemeInfo, '');
            $Active = $ThemeFolder == $this->Data('EnabledThemeFolder');
            $Version = GetValue('Version', $ThemeInfo, '');
            $ThemeUrl = GetValue('Url', $ThemeInfo, '');
            $Author = GetValue('Author', $ThemeInfo, '');
            $AuthorUrl = GetValue('AuthorUrl', $ThemeInfo, '');
            $NewVersion = GetValue('NewVersion', $ThemeInfo, '');
            $Upgrade = $NewVersion != '' && version_compare($NewVersion, $Version, '>');
            $PreviewUrl = GetValue('ScreenshotUrl', $ThemeInfo, FALSE);
            $Description = GetValue('Description', $ThemeInfo);
            $RequiredApplications = GetValue('RequiredApplications', $ThemeInfo, FALSE);

            $ClassCurrentTheme = ($EnabledThemeInfo['Index'] == $ThemeInfo['Index'])
               ? 'current-theme'
               : '';

            $UpgradeHtml = ($Upgrade)
               ? '<div class="Alert">' . Anchor(sprintf(T('%1$s version %2$s is available.'), $ScreenName, $NewVersion), CombinePaths(array($AddonUrl, 'find', urlencode($ThemeName)), '/')) . '</div>'
               : '';

            $PreviewImageHtml = ($PreviewUrl !== FALSE)
               ? Anchor(Img($PreviewUrl, array('alt' => $ScreenName)), 'dashboard/settings/previewtheme/'.$ThemeName, '', array('target' => '_top', 'class' => 'theme-image'))
               : '';

            $DescriptionHtml = ($Description)
               ? '<em class="theme-description">' . $Description . '</em>'
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
            $ColClass .= $Active ? ' Enabled' : '';
            $ColClass .= $PreviewUrl ? ' HasPreview' : '';
         ?>

         <td class="themeblock <?php echo $ClassCurrentTheme; ?> <?php echo $ColClass; ?>">
            <h4>
               <?php echo ($ThemeUrl != '') ? Anchor($ScreenName, $ThemeUrl) : $ScreenName; ?>
               <span class="theme-version">
                  <?php echo $Version; ?>
               </span>
            </h4>

            <div class="author-name">
               <?php echo $Author; ?>
            </div>

            <?php echo $UpgradeHtml; ?>
            <?php echo $PreviewImageHtml; ?>
            <?php echo $DescriptionHtml; ?>

            <?php
               if ($this->Data('EnabledTheme.Options')) {
               $OptionsDescription = sprintf(T('This theme has additional options.', 'This theme has additional options on the %s page.'),
                  Anchor(T('Mobile Theme Options'), '/dashboard/settings/mobilethemeoptions'));

               echo '<div class="Options">',
                  $OptionsDescription,
                  '</div>';
               }
            ?>

            <?php $this->FireEvent('AfterCurrentTheme'); ?>

            <?php if (is_array($RequiredApplications)): ?>

               <dl>
                  <dt><?php echo T('Requires'); ?></dt>
                  <dd>

                     <?php
                        $i = 0;
                        foreach ($RequiredApplications as $RequiredApplication => $VersionInfo) {
                           if ($i > 0) {
                              echo ', ';
                           }
                           printf(T('%1$s %2$s'), $RequiredApplication, $VersionInfo);
                           ++$i;
                        }
                     ?>
               </dl>

            <?php endif; ?>

            <div class="Buttons">

               <?php if (!$ClassCurrentTheme): ?>

                  <?php
                     echo Anchor(T('Apply'), 'dashboard/settings/mobilethemes/'.$ThemeName.'/'.$Session->TransientKey(), 'SmallButton EnableAddon EnableTheme', array('target' => '_top'));
                     echo Anchor(T('Preview'), 'dashboard/settings/previewtheme/'.$ThemeName, 'SmallButton PreviewAddon', array('target' => '_top'));
                     $this->EventArguments['ThemeInfo'] = $ThemeInfo;
                     $this->FireEvent('AfterThemeButtons');
                  ?>

               <?php else: ?>

                  <div class="theme-applied">Enabled</div>

               <?php endif; ?>

            </div>
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
