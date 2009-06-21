<?php if (!defined('APPLICATION')) exit();
$UpdateUrl = Gdn::Config('Garden.UpdateCheckUrl');
?>
<h1><?php echo Gdn::Translate('Manage Applications'); ?></h1>
<p><?php
   printf(
      Translate("Applications allow you to add large groups of functionality to your site.<br />Once an application has been added to your %s folder, you can enable or disable it here."),
      '<span class="Warning">'.PATH_APPLICATIONS.'</span>'
   ); ?></p>
<p><?php echo Anchor('Get More Applications', 'http://lussumo.com/addons', 'Button'); ?></p>
<?php echo $this->Form->Errors(); ?>
<table class="AltRows">
   <thead>
      <tr>
         <th><?php echo Gdn::Translate('Application'); ?></th>
         <th class="Alt"><?php echo Gdn::Translate('Version'); ?></th>
         <th><?php echo Gdn::Translate('Description'); ?></th>
         <th class="Alt"><?php echo Gdn::Translate('Requires'); ?></th>
         <th><?php echo Gdn::Translate('Options'); ?></th>
      </tr>
   </thead>
   <tbody>
<?php
$Alt = FALSE;
foreach ($this->AvailableApplications as $AppName => $AppInfo) {
   $Alt = $Alt ? FALSE : TRUE;
   $AppVersion = ArrayValue('Version', $AppInfo, '');
   $CurrentVersion = $this->UpdateManager->GetCurrentVersion(ADDON_TYPE_APPLICATION, $AppName);
   if (is_numeric($CurrentVersion) && is_numeric($AppVersion) && $AppVersion < $CurrentVersion) {
      ?>
      <tr class="FootNote">
         <td colspan="5"><a href="<?php
            echo CombinePaths(array($UpdateUrl, 'find', urlencode($AppName)), '/');
         ?>"><?php
            printf(Gdn::Translate('%1$s version %2$s is available.'), $AppName, $CurrentVersion);
         ?></a></td>
      </tr>
      <?php
   }
   ?>   
   <tr<?php echo $Alt ? ' class="Alt"' : ''; ?>>
      <th><?php echo Anchor($AppName, ArrayValue('Url', $AppInfo, '')); ?></th>
      <td class="Alt"><?php echo ArrayValue('Version', $AppInfo, ''); ?></td>
      <td><?php echo ArrayValue('Description', $AppInfo, ''); ?></td>
      <td class="Alt">
         <?php
         $RequiredApplications = ArrayValue('RequiredApplications', $AppInfo, FALSE);
         if ($RequiredApplications === FALSE) {
            echo '&nbsp;';
         } else {
            $i = 0;
            foreach ($RequiredApplications as $RequiredApp => $VersionInfo) {
               if ($i > 0)
                  echo ', ';
                  
               printf(Gdn::Translate('%1$s (version %2$s'), $RequiredApp, $VersionInfo);
               ++$i;
            }
         }
         ?>
      </td>
      <td>
         <?php
         $Action = array_key_exists($AppName, $this->EnabledApplications) ? 'Disable' : 'Enable';
         $Allow = ArrayValue('Allow'.$Action, $AppInfo, TRUE);
         if ($Allow) {
            echo $this->Form->Open()
            .$this->Form->Hidden('ApplicationName', array('value' => $AppName))
            .$this->Form->Button($Action)
            .$this->Form->Close();
         } else {
            echo '&nbsp;';
         }
         ?>
      </td>
   </tr>
<?php } ?>
   </tbody>
</table>
<h2><?php echo Gdn::Translate('Problems?'); ?></h2>
<p><?php echo Translate("If something goes wrong and this page stops functioning properly, you can disable applications manually by editing:");?></p>
<p class="Warning"><?php echo PATH_CONF.DS.'config.php'; ?></p>