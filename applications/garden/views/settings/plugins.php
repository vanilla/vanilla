<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$UpdateUrl = Gdn::Config('Garden.UpdateCheckUrl');
$AddonUrl = Gdn::Config('Garden.AddonUrl', '');
?>
   <h1><?php echo Gdn::Translate('Manage Plugins'); ?></h1>
   <?php
   // Build a filter menu for plugins
   $PluginCount = count($this->AvailablePlugins);
   $EnabledCount = count($this->EnabledPlugins);
   $DisabledCount = $PluginCount - $EnabledCount;
   echo '<div class="FilterMenu">',
      $this->Filter == '' ? '<strong>'.Translate('All').'</strong>' : Anchor('All', '/settings/plugins/'),
      ' ('.$PluginCount.') <span>|</span> ',
      $this->Filter == 'enabled' ? '<strong>'.Translate('Enabled').'</strong>' : Anchor('Enabled', '/settings/plugins/enabled'),
      ' ('.$EnabledCount.') <span>|</span> ',
      $this->Filter == 'disabled' ? '<strong>'.Translate('Disabled').'</strong>' : Anchor('Disabled', '/settings/plugins/disabled'),
      ' ('.$DisabledCount.')';
      
   if ($AddonUrl != '')
      echo ' <span>|</span> '.Anchor('Get More Plugins', $AddonUrl);
      
   ?>
   </div>
   <div class="Info">
      <?php
      printf(
         Translate('PluginHelp'),
         '<span class="Warning">'.PATH_PLUGINS.'</span>'
      );
      ?>
   </div>
   <?php   
   echo $this->Form->Errors();
   ?>
   <table class="AltRows">
      <thead>
         <tr>
            <th><?php echo Gdn::Translate('Plugin'); ?></th>
            <th><?php echo Gdn::Translate('Description'); ?></th>
         </tr>
      </thead>
      <tbody>
   <?php
   $Alt = FALSE;
   foreach ($this->AvailablePlugins as $PluginName => $PluginInfo) {
      $Css = array_key_exists($PluginName, $this->EnabledPlugins) ? 'Enabled' : 'Disabled';
      $State = strtolower($Css);
      if ($this->Filter == '' || $this->Filter == $State) {
         $Alt = $Alt ? FALSE : TRUE;
         $Version = ArrayValue('Version', $PluginInfo, '');
         $ScreenName = ArrayValue('Name', $PluginInfo, $PluginName);
         $SettingsUrl = $State == 'enabled' ? ArrayValue('SettingsUrl', $PluginInfo, '') : '';
         $PluginUrl = ArrayValue('PluginUrl', $PluginInfo, '');
         $Author = ArrayValue('Author', $PluginInfo, '');
         $AuthorUrl = ArrayValue('AuthorUrl', $PluginInfo, '');
         $NewVersion = ArrayValue('NewVersion', $PluginInfo, '');
         $Upgrade = $NewVersion != '' && version_compare($NewVersion, $Version, '>');
         $RowClass = $Css;
         if ($Alt) $RowClass .= ' Alt';
         ?>
         <tr class="More <?php echo $RowClass; ?>">
            <th><?php echo $ScreenName; ?></th>
            <td class="Alt"><?php echo ArrayValue('Description', $PluginInfo, ''); ?></td>
         </tr>
         <tr class="<?php echo ($Upgrade ? 'More ' : '').$RowClass; ?>">
            <td class="Info"><?php
               echo Anchor(
                  array_key_exists($PluginName, $this->EnabledPlugins) ? 'Disable' : 'Enable',
                  '/settings/plugins/'.$PluginName.'/'.$Session->TransientKey()
               );
               
               if ($SettingsUrl != '') {
                  echo '<span>|</span>';
                  echo Anchor('Settings', $SettingsUrl);
               }
            ?></td>
            <td class="Alt Info"><?php
               $RequiredApplications = ArrayValue('RequiredApplications', $PluginInfo, FALSE);
               $RequiredPlugins = ArrayValue('RequiredPlugins', $PluginInfo, FALSE);
               $Info = '';
               if ($Version != '')
                  $Info = sprintf(Translate('Version %s'), $Version);
                  
               if (is_array($RequiredApplications) || is_array($RequiredPlugins)) {
                  if ($Info != '')
                     $Info .= '<span>|</span>';

                  $Info .= Translate('Requires: ');
               }
                  
               $i = 0;
               if (is_array($RequiredApplications)) {
                  if ($i > 0)
                     $Info .= ', ';
                  
                  foreach ($RequiredApplications as $RequiredApplication => $VersionInfo) {   
                     $Info .= sprintf(Gdn::Translate('%1$s Version %2$s'), $RequiredApplication, $VersionInfo);
                     ++$i;
                  }
               }
               
               if ($RequiredPlugins !== FALSE) {
                  foreach ($RequiredPlugins as $RequiredPlugin => $VersionInfo) {
                     if ($i > 0)
                        $Info .= ', ';
                        
                     $Info .= sprintf(Gdn::Translate('%1$s Version %2$s'), $RequiredPlugin, $VersionInfo);
                     ++$i;
                  }
               }

               if ($Author != '') {
                  $Info .= '<span>|</span>';
                  $Info .= sprintf('By %s', $AuthorUrl != '' ? Anchor($Author, $AuthorUrl) : $Author);
               }
               
               if ($PluginUrl != '') {
                  $Info .= '<span>|</span>';
                  $Info .= Anchor('Visit Site', $PluginUrl);
               }
               
               echo $Info != '' ? $Info : '&nbsp;';
                  
            ?></td>
         </tr>
         <?php
         if ($Upgrade) {
            ?>
            <tr class="<?php echo $RowClass; ?>">
               <td colspan="2"><div class="Alert"><a href="<?php
                  echo CombinePaths(array($AddonUrl, 'find', urlencode($PluginName)), '/');
               ?>"><?php
                  printf(Gdn::Translate('%1$s version %2$s is available.'), $ScreenName, $NewVersion);
               ?></a></div></td>
            </tr>
         <?php
         }
      }
   }
   ?>
      </tbody>
   </table>
   <?php
   printf(Translate('AddonProblems'), '<p class="Warning">'.PATH_CONF.DS.'config.php'.'</p>');
   