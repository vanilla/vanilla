<?php if (!defined('APPLICATION')) exit();
$UpdateUrl = Gdn::Config('Garden.UpdateCheckUrl');
?>
   <h1><?php echo Gdn::Translate('Manage Plugins'); ?></h1>
   <p><?php
      printf(
         Translate("Plugins allow you to add functionality to your site.<br />Once a plugin has been added to your %s folder, you can enable or disable it here."),
         '<span class="Warning">'.PATH_PLUGINS.'</span>'
      ); ?></p>
   <p><?php echo Anchor('Get More Plugins', 'http://lussumo.com/addons', 'Button'); ?></p>   
   <?php echo $this->Form->Errors(); ?>
   <table class="AltRows">
      <thead>
         <tr>
            <th><?php echo Gdn::Translate('Plugin'); ?></th>
            <th class="Alt"><?php echo Gdn::Translate('Version'); ?></th>
            <th><?php echo Gdn::Translate('Description'); ?></th>
            <th class="Alt"><?php echo Gdn::Translate('Requires'); ?></th>
            <th><?php echo Gdn::Translate('Options'); ?></th>
         </tr>
      </thead>
      <tbody>
   <?php
   $Alt = FALSE;
   foreach ($this->AvailablePlugins as $PluginName => $PluginInfo) {
      $Alt = $Alt ? FALSE : TRUE;
      $PluginVersion = ArrayValue('Version', $PluginInfo, '');
      $ScreenName = ArrayValue('Name', $PluginInfo, $PluginName);
      $CurrentVersion = $this->UpdateManager->GetCurrentVersion(ADDON_TYPE_PLUGIN, $PluginName);
      if (is_numeric($CurrentVersion) && is_numeric($PluginVersion) && $PluginVersion < $CurrentVersion) {
         ?>
         <tr class="FootNote">
            <td colspan="5"><a href="<?php
               echo CombinePaths(array($UpdateUrl, 'find', urlencode($PluginName)), '/');
            ?>"><?php
               printf(Gdn::Translate('%1$s version %2$s is available.'), $ScreenName, $CurrentVersion);
            ?></a></td>
         </tr>
         <?php
      }
      ?>
      <tr<?php echo $Alt ? ' class="Alt"' : ''; ?>>
         <th><?php echo $ScreenName; ?></th>
         <td class="Alt"><?php echo $PluginVersion; ?></td>
         <td><?php echo ArrayValue('Description', $PluginInfo, ''); ?></td>
         <td class="Alt">
            <?php
            $RequiredApplications = ArrayValue('RequiredApplications', $PluginInfo, FALSE);
            
            $i = 0;
            if (is_array($RequiredApplications)) {
               if ($i > 0)
                  echo ', ';
               
               foreach ($RequiredApplications as $RequiredApplication => $VersionInfo) {   
                  printf(Gdn::Translate('%1$s (version %2$s)'), $RequiredApplication, $VersionInfo);
                  ++$i;
               }
            }
            
            $RequiredPlugins = ArrayValue('RequiredPlugins', $PluginInfo, FALSE);
            if ($RequiredPlugins !== FALSE) {
               foreach ($RequiredPlugins as $RequiredPlugin => $VersionInfo) {
                  if ($i > 0)
                     echo ', ';
                     
                  printf(Gdn::Translate('%1$s (version %2$s)'), $RequiredPlugin, $VersionInfo);
                  ++$i;
               }
            }
            
            if($i == 0)
               echo '&nbsp;';
            ?>
         </td>
         <td>
            <?php
            echo $this->Form->Open()
               .$this->Form->Hidden('PluginName', array('value' => $PluginName))
               .$this->Form->Button(array_key_exists($PluginName, $this->EnabledPlugins) ? 'Disable' : 'Enable')
               .$this->Form->Close();
            ?>
         </td>
      </tr>
      <?php
   }
   ?>
      </tbody>
   </table>
   <h2><?php echo Gdn::Translate('Problems?'); ?></h2>
   <p><?php echo Translate("If something goes wrong with a plugin and you can't use your site, you can disable plugins manually by editing:"); ?></p>
   <p class="Warning"><?php echo PATH_CONF.DS.'config.php'; ?></p>