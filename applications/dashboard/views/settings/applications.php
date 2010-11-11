<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$UpdateUrl = C('Garden.UpdateCheckUrl');
$AddonUrl = C('Garden.AddonUrl');
$AppCount = count($this->AvailableApplications);
$EnabledCount = count($this->EnabledApplications);
$DisabledCount = $AppCount - $EnabledCount;
?>
<h1><?php echo T('Manage Applications'); ?></h1>
<div class="Info">
   <?php
   printf(
      T('ApplicationHelp'),
      '<code>'.PATH_APPLICATIONS.'</code>'
   );
   ?>
</div>
<div class="Tabs FilterTabs">
   <ul>
      <li<?php echo $this->Filter == 'all' ? ' class="Active"' : ''; ?>><?php echo Anchor(T('All '.Wrap($AppCount)), 'settings/applications/'); ?></li>
      <li<?php echo $this->Filter == 'enabled' ? ' class="Active"' : ''; ?>><?php echo Anchor(T('Enabled '.Wrap($EnabledCount)), 'settings/applications/enabled'); ?></li>
      <li<?php echo $this->Filter == 'disabled' ? ' class="Active"' : ''; ?>><?php echo Anchor(T('Disabled '.Wrap($DisabledCount)), 'settings/applications/disabled'); ?></li>
      <?php
      if ($AddonUrl != '')
         echo Wrap(Anchor(T('Get More Applications'), $AddonUrl), 'li');
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
         <th><?php echo T('Application'); ?></th>
         <th class="Alt"><?php echo T('Description'); ?></th>
      </tr>
   </thead>
   <tbody>
<?php
$Alt = FALSE;
foreach ($this->AvailableApplications as $AppName => $AppInfo) {
   $Css = array_key_exists($AppName, $this->EnabledApplications) ? 'Enabled' : 'Disabled';
   $State = strtolower($Css);
   if ($this->Filter == 'all' || $this->Filter == $State) {
      $Alt = $Alt ? FALSE : TRUE;
      $Version = ArrayValue('Version', $AppInfo, '');
      $ScreenName = ArrayValue('Name', $AppInfo, $AppName);
      $SettingsUrl = $State == 'enabled' ? ArrayValue('SettingsUrl', $AppInfo, '') : '';
      $AppUrl = ArrayValue('Url', $AppInfo, '');
      $Author = ArrayValue('Author', $AppInfo, '');
      $AuthorUrl = ArrayValue('AuthorUrl', $AppInfo, '');
      $NewVersion = ArrayValue('NewVersion', $AppInfo, '');
      $Upgrade = $NewVersion != '' && version_compare($NewVersion, $Version, '>');
      $RowClass = $Css;
      if ($Alt) $RowClass .= ' Alt';
      ?>   
      <tr class="More <?php echo $RowClass; ?>">
         <th><?php echo $ScreenName; ?></th>
         <td><?php echo ArrayValue('Description', $AppInfo, ''); ?></td>
      </tr>
      <tr class="<?php echo ($Upgrade ? 'More ' : '').$RowClass; ?>">
         <td class="Info"><?php
            $ToggleText = array_key_exists($AppName, $this->EnabledApplications) ? 'Disable' : 'Enable';
            echo Anchor(
               T($ToggleText),
               '/settings/applications/'.$this->Filter.'/'.$AppName.'/'.$Session->TransientKey(),
               $ToggleText.'Addon SmallButton'
            );
            
            if ($SettingsUrl != '') {
               echo Anchor(T('Settings'), $SettingsUrl, 'SmallButton');
            }
         ?></td>
         <td class="Alt Info"><?php
            $RequiredApplications = ArrayValue('RequiredApplications', $AppInfo, FALSE);
            $Info = '';
            if ($Version != '')
               $Info = sprintf(T('Version %s'), $Version);
               
            if (is_array($RequiredApplications)) {
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

            if ($Author != '') {
               $Info .= '<span>|</span>';
               $Info .= sprintf('By %s', $AuthorUrl != '' ? Anchor($Author, $AuthorUrl) : $Author);
            }
            
            if ($AppUrl != '') {
               $Info .= '<span>|</span>';
               $Info .= Anchor(T('Visit Site'), $AppUrl);
            }
            
            echo $Info != '' ? $Info : '&nbsp;';
            ?>
         </td>
      </tr>
      <?php
      if ($Upgrade) {
         ?>
         <tr class="<?php echo $RowClass; ?>">
            <td colspan="2"><div class="Alert"><a href="<?php
               echo CombinePaths(array($UpdateUrl, 'find', urlencode($AppName)), '/');
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