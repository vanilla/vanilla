<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$UpdateUrl = C('Garden.UpdateCheckUrl');
$AddonUrl = C('Garden.AddonUrl');
$PluginCount = count($this->AvailablePlugins);
$EnabledCount = count($this->EnabledPlugins);
$DisabledCount = $PluginCount - $EnabledCount;
?>
<script type="text/javascript">
jQuery(document).ready(function($) {
   var selectors = '.plugins-all, .plugins-enabled, .plugins-disabled';
   $(selectors).click(function() {
      $(selectors).parents('li').removeClass('Active');
      $(this).parents('li').addClass('Active');
      if ($(this).hasClass('plugins-disabled')) {
         $('tr.Enabled').hide();
         $('tr.Disabled').show();
      } else if ($(this).hasClass('plugins-enabled')) {
         $('tr.Enabled').show();
         $('tr.Disabled').hide();
      } else {
         $('tr.Enabled').show();
         $('tr.Disabled').show();
      }
      return false;
   });
});
</script>
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
      <li<?php echo $this->Filter == 'all' ? ' class="Active"' : ''; ?>><?php echo Anchor(sprintf(T('All %1$s'), Wrap($PluginCount)), 'settings/plugins/all', 'plugins-all'); ?></li>
      <li<?php echo $this->Filter == 'enabled' ? ' class="Active"' : ''; ?>><?php echo Anchor(sprintf(T('Enabled %1$s'), Wrap($EnabledCount)), 'settings/plugins/enabled', 'plugins-enabled'); ?></li>
      <li<?php echo $this->Filter == 'disabled' ? ' class="Active"' : ''; ?>><?php echo Anchor(sprintf(T('Disabled %1$s'), Wrap($DisabledCount)), 'settings/plugins/disabled', 'plugins-disabled'); ?></li>
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
         <th colspan="2"><?php echo T('Plugin'); ?></th>
         <th><?php echo T('Description'); ?></th>
      </tr>
   </thead>
   <tbody>
<?php
$Alt = FALSE;
foreach ($this->AvailablePlugins as $PluginName => $PluginInfo) {
   // Skip Hidden & Trigger plugins
   if (isset($PluginInfo['Hidden']) && $PluginInfo['Hidden'] === TRUE) 
      continue;
   if (isset($PluginInfo['Trigger']) && $PluginInfo['Trigger'] == TRUE) // Any 'true' value.
      continue;
   
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
      $IconPath = '/plugins/'.GetValue('Folder', $PluginInfo, '').'/icon.png';
      $IconPath = file_exists(PATH_ROOT.$IconPath) ? $IconPath : 'applications/dashboard/design/images/plugin-icon.png';
      ?>
      <tr <?php echo 'id="'.Gdn_Format::Url(strtolower($PluginName)).'-plugin"', ' class="More '.$RowClass.'"'; ?>>
         <td rowspan="2" class="Less"><?php echo Img($IconPath, array('class' => 'PluginIcon')); ?></td>
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
            
            echo $Info != '' ? $Info : '&#160;';
               
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
