<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$UpdateUrl = Gdn::Config('Garden.UpdateCheckUrl');
$AddonUrl = Gdn::Config('Garden.AddonUrl');
?>
<h1><?php echo Gdn::Translate('Manage Applications'); ?></h1>
<?php
// Build a filter menu for applications
$AppCount = count($this->AvailableApplications);
$EnabledCount = count($this->EnabledApplications);
$DisabledCount = $AppCount - $EnabledCount;
echo '<div class="FilterMenu">',
   $this->Filter == '' ? '<strong>'.Translate('All').'</strong>' : Anchor('All', '/settings/plugins/'),
   ' ('.$AppCount.') <span>|</span> ',
   $this->Filter == 'enabled' ? '<strong>'.Translate('Enabled').'</strong>' : Anchor('Enabled', '/settings/applications/enabled'),
   ' ('.$EnabledCount.') <span>|</span> ',
   $this->Filter == 'disabled' ? '<strong>'.Translate('Disabled').'</strong>' : Anchor('Disabled', '/settings/applications/disabled'),
   ' ('.$DisabledCount.')';
   
if ($AddonUrl != '')
   echo ' <span>|</span> '.Anchor('Get More Applications', $AddonUrl);
?>
</div>
<div class="Info">
   <?php
   printf(
      Translate('ApplicationHelp'),
      '<span class="Warning">'.PATH_APPLICATIONS.'</span>'
   );
   ?>
</div>
<table class="AltRows">
   <thead>
      <tr>
         <th><?php echo Gdn::Translate('Application'); ?></th>
         <th class="Alt"><?php echo Gdn::Translate('Description'); ?></th>
      </tr>
   </thead>
   <tbody>
<?php
$Alt = FALSE;
foreach ($this->AvailableApplications as $AppName => $AppInfo) {
   $Css = array_key_exists($AppName, $this->EnabledApplications) ? 'Enabled' : 'Disabled';
   $State = strtolower($Css);
   if ($this->Filter == '' || $this->Filter == $State) {
      $Alt = $Alt ? FALSE : TRUE;
      $AppVersion = ArrayValue('Version', $AppInfo, '');
      $ScreenName = ArrayValue('Name', $AppInfo, $AppName);
      $SettingsUrl = $State == 'enabled' ? ArrayValue('SettingsUrl', $AppInfo, '') : '';
      $AppUrl = ArrayValue('Url', $AppInfo, '');
      $Author = ArrayValue('Author', $AppInfo, '');
      $AuthorUrl = ArrayValue('AuthorUrl', $AppInfo, '');
      $CurrentVersion = $this->UpdateManager->GetCurrentVersion(ADDON_TYPE_APPLICATION, $AppName);
      $Upgrade = is_numeric($CurrentVersion) && is_numeric($AppVersion) && $AppVersion < $CurrentVersion;
      $CssClass = $Upgrade ? 'More' : '';
      if ($Alt) $CssClass = $CssClass . ' Alt';
      $CssClass = trim($CssClass);
      if ($CssClass != '')
         $CssClass = ' class="'.$CssClass.'"';
      ?>   
      <tr class="More <?php echo $Css . ($Alt ? ' Alt' : ''); ?>">
         <th><?php echo $ScreenName; ?></th>
         <td><?php echo ArrayValue('Description', $AppInfo, ''); ?></td>
      </tr>
      <tr class="<?php echo $Css . ($Alt ? ' Alt' : ''); ?>">
         <td class="Info"><?php
            echo Anchor(
               array_key_exists($AppName, $this->EnabledApplications) ? 'Disable' : 'Enable',
               '/settings/applications/'.$AppName.'/'.$Session->TransientKey()
            );
            
            if ($SettingsUrl != '') {
               echo '<span>|</span>';
               echo Anchor('Settings', $SettingsUrl);
            }
         ?></td>
         <td class="Alt Info"><?php
            $RequiredApplications = ArrayValue('RequiredApplications', $AppInfo, FALSE);
            $Info = '';
            if ($AppVersion != '')
               $Info = sprintf(Translate('Version %s'), $AppVersion);
               
            if (is_array($RequiredApplications)) {
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

            if ($Author != '') {
               $Info .= '<span>|</span>';
               $Info .= sprintf('By %s', $AuthorUrl != '' ? Anchor($Author, $AuthorUrl) : $Author);
            }
            
            if ($AppUrl != '') {
               $Info .= '<span>|</span>';
               $Info .= Anchor('Visit Application Site', $AppUrl);
            }
            
            echo $Info != '' ? $Info : '&nbsp;';
            ?>
         </td>
      </tr>
      <?php
      if (is_numeric($CurrentVersion) && is_numeric($AppVersion) && $AppVersion < $CurrentVersion) {
         ?>
         <tr class="FootNote">
            <td colspan="2"><a href="<?php
               echo CombinePaths(array($UpdateUrl, 'find', urlencode($AppName)), '/');
            ?>"><?php
               printf(Gdn::Translate('%1$s version %2$s is available.'), $AppName, $CurrentVersion);
            ?></a></td>
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