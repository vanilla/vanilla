<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo Gdn::Translate('Manage Themes'); ?></h1>
<?php echo $this->Form->Errors(); ?>
<p><?php
   printf(
      Translate("Themes allow you to change the colors, fonts, and layout of your site.<br />Once a theme has been added to your %s folder, you can enable it here."),
      '<span class="Warning">'.PATH_THEMES.'</span>'
   ); ?></p>
<p><?php echo Anchor('Get More Themes', 'http://lussumo.com/addons', 'Button'); ?></p>   
<table class="AltRows">
   <thead>
      <tr>
         <th><?php echo Gdn::Translate('Theme'); ?></th>
         <th class="Alt"><?php echo Gdn::Translate('Version'); ?></th>
         <th><?php echo Gdn::Translate('Description'); ?></th>
         <th class="Alt"><?php echo Gdn::Translate('Requires'); ?></th>
         <th><?php echo Gdn::Translate('Options'); ?></th>
      </tr>
   </thead>
   <tbody>
<?php
$Alt = FALSE;
foreach ($this->AvailableThemes as $ThemeName => $ThemeInfo) {
   $Alt = $Alt ? FALSE : TRUE;
   $CssClass = $Alt ? 'Alt' : '';
   $ThemeFolder = ArrayValue('Folder', $ThemeInfo, '');
   $Active = $ThemeFolder == $this->EnabledTheme;
   $CssClass .= $Active ? ' Highlight' : '';
   $CssClass = trim($CssClass);
   ?>
   <tr<?php echo $CssClass != '' ? ' class="'.$CssClass.'"' : ''; ?>>
      <th><?php echo Anchor($ThemeName, ArrayValue('Url', $ThemeInfo)); ?></th>
      <td class="Alt"><?php echo ArrayValue('Version', $ThemeInfo, '&nbsp;'); ?></td>
      <td><?php echo ArrayValue('Description', $ThemeInfo, '&nbsp;'); ?></td>
      <td class="Alt">
      <?php
         $RequiredApps = ArrayValue('RequiredApplications', $ThemeInfo, FALSE);
         if ($RequiredApps === FALSE) {
            echo '&nbsp;';
         } else {
            $i = 0;
            foreach ($RequiredApps as $RequiredApp => $VersionInfo) {
               if ($i > 0)
                  echo ', ';
                  
               printf(Gdn::Translate('%1$s %2$s'), $RequiredApp, $VersionInfo);
               ++$i;
            }
         }
      ?>
      </td>
      <td class="nowrap">
         <?php
         if ($Active) {
            echo Gdn::Translate('Current');
         } else {
            $Session = Gdn::Session();
            echo Anchor('Apply', 'garden/settings/themes/'.$ThemeFolder.'/'.$Session->TransientKey(), 'Button', array('target' => '_top'));
            echo ' ';
            echo Anchor('Preview', 'garden/settings/previewtheme/'.$ThemeFolder, 'Button', array('target' => '_top'));
         }
         ?>
      </td>
   </tr>
   <?php
}
?>
   </tbody>
</table>
<h2><?php echo Gdn::Translate('Problems?'); ?></h2>
<p><?php echo Translate("If something goes wrong with a theme and you can't use your site, you can disable themes manually by removing the \"Theme\" configuration setting in:"); ?></p>
<p class="Warning"><?php echo PATH_CONF.DS.'config.php'; ?></p>

