<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$UpdateUrl = C('Garden.UpdateCheckUrl');
$AddonUrl = C('Garden.AddonUrl');
$PluginCount = count($this->AvailablePlugins);
$EnabledCount = count($this->EnabledPlugins);
$DisabledCount = $PluginCount - $EnabledCount;
?>
<h1><?php echo T('Manage Plugins'); ?></h1>
<div class="Info">
   <?php
   printf(
      T('PluginHelp'),
      '<code>'.PATH_PLUGINS.'</code>'
   );
   ?>
</div>
<div class="Tabs FilterTabs">
   <ul>
      <li<?php echo $this->Filter == 'all' ? ' class="Active"' : ''; ?>><?php echo Anchor(T('All ').Wrap($PluginCount), 'settings/plugins/all'); ?></li>
      <li<?php echo $this->Filter == 'enabled' ? ' class="Active"' : ''; ?>><?php echo Anchor(T('Enabled ').Wrap($EnabledCount), 'settings/plugins/enabled'); ?></li>
      <li<?php echo $this->Filter == 'disabled' ? ' class="Active"' : ''; ?>><?php echo Anchor(T('Disabled ').Wrap($DisabledCount), 'settings/plugins/disabled'); ?></li>
      <?php
      if ($AddonUrl != '')
         echo Wrap(Anchor(T('Get More Plugins'), $AddonUrl), 'li');
      ?>
   </ul>
</div>
<?php echo $this->Form->Errors(); ?>
<div class="Messages Errors TestAddonErrors Hidden">
   <ul>
      <li><?php echo T('The addon could not be enabled because it generated a fatal error: <pre>%s</pre>'); ?></li>
   </ul>
</div>
<table class="AltRows">
   <thead>
      <tr>
         <th><?php echo T('Plugin'); ?></th>
         <th><?php echo T('Description'); ?></th>
      </tr>
   </thead>
   <tbody>
<?php
$Alt = FALSE;
foreach ($this->AvailablePlugins as $PluginName => $PluginInfo) {
   if (isset($PluginInfo['Hidden']) && $PluginInfo['Hidden'] === TRUE) continue;
   $Css = array_key_exists($PluginName, $this->EnabledPlugins) ? 'Enabled' : 'Disabled';
   $State = strtolower($Css);
   if ($this->Filter == 'all' || $this->Filter == $State) {
      $Alt = $Alt ? FALSE : TRUE;
      $Version = Gdn_Format::Display(GetValue('Version', $PluginInfo, ''));
      $ScreenName = Gdn_Format::Display(GetValue('Name', $PluginInfo, $PluginName));
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
         <td class="Alt"><?php echo Gdn_Format::Html(GetValue('Description', $PluginInfo, '')); ?></td>
      </tr>
      <tr class="<?php echo ($Upgrade ? 'More ' : '').$RowClass; ?>">
         <td class="Info"><?php
            $ToggleText = array_key_exists($PluginName, $this->EnabledPlugins) ? 'Disable' : 'Enable';
            echo Anchor(
               T($ToggleText),
               '/settings/plugins/'.$this->Filter.'/'.$PluginName.'/'.$Session->TransientKey(),
               $ToggleText . 'Addon SmallButton'
            );
            
            if ($SettingsUrl != '')
               echo Anchor(T('Settings'), $SettingsUrl, 'SmallButton');
            
            if (SettingsModule::IsRemovable(SettingsModule::TYPE_PLUGIN, $PluginName))
               echo Anchor(T('Remove'), '/settings/removeaddon/'.SettingsModule::TYPE_PLUGIN.'/'.$PluginName.'/'.$Session->TransientKey(), 'RemoveItem SmallButton');

         ?></td>
         <td class="Alt Info"><?php
            $RequiredApplications = ArrayValue('RequiredApplications', $PluginInfo, FALSE);
            $RequiredPlugins = ArrayValue('RequiredPlugins', $PluginInfo, FALSE);
            $Info = '';
            if ($Version != '')
               $Info = sprintf(T('Version %s'), $Version);
               
            if (is_array($RequiredApplications) || is_array($RequiredPlugins)) {
               if ($Info != '')
                  $Info .= '<span>|</span>';

               $Info .= T('Requires: ');
            }
               
            $i = 0;
            if (is_array($RequiredApplications)) {
               if ($i > 0)
                  $Info .= ', ';
               
               foreach ($RequiredApplications as $RequiredApplication => $VersionInfo) {   
                  $Info .= sprintf(T('%1$s Version %2$s'), $RequiredApplication, $VersionInfo);
                  ++$i;
               }
            }
            
            if ($RequiredPlugins !== FALSE) {
               foreach ($RequiredPlugins as $RequiredPlugin => $VersionInfo) {
                  if ($i > 0)
                     $Info .= ', ';
                     
                  $Info .= sprintf(T('%1$s Version %2$s'), $RequiredPlugin, $VersionInfo);
                  ++$i;
               }
            }

            if ($Author != '') {
               $Info .= '<span>|</span>';
               $Info .= sprintf(T('By %s'), $AuthorUrl != '' ? Anchor($Author, $AuthorUrl) : $Author);
            }
            
            if ($PluginUrl != '') {
               $Info .= '<span>|</span>';
               $Info .= Anchor(T('Visit Site'), $PluginUrl);
            }
            
            echo $Info != '' ? $Info : '&nbsp;';
               
         ?></td>
      </tr>
      <?php
      if ($Upgrade) {
         ?>
         <tr class="<?php echo $RowClass; ?>">
            <td colspan="2"><div class="Alert"><a href="<?php
               echo CombinePaths(array($UpdateUrl, 'find', urlencode($ScreenName)), '/');
            ?>"><?php
               printf(T('%1$s version %2$s is available.'), $ScreenName, $NewVersion);
            ?></a></div></td>
         </tr>
      <?php
      }
   }
}
?>
   </tbody>
</table>