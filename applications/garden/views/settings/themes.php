<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$AddonUrl = Gdn::Config('Garden.AddonUrl');
?>
<h1><?php echo Gdn::Translate('Manage Themes'); ?></h1>
<?php
if ($AddonUrl != '')
   echo '<div class="FilterMenu">',
      Anchor('Get More Themes', $AddonUrl),
      '</div>';
         
?>
<div class="Info">
<?php
printf(
   Translate('ThemeHelp'),
   '<span class="Warning">'.PATH_THEMES.'</span>'
);
?></div>
<table class="AltRows">
   <thead>
      <tr>
         <th><?php echo Gdn::Translate('Theme'); ?></th>
         <th><?php echo Gdn::Translate('Description'); ?></th>
      </tr>
   </thead>
   <tbody>
<?php
$Alt = FALSE;
foreach ($this->AvailableThemes as $ThemeName => $ThemeInfo) {
   $ThemeFolder = ArrayValue('Folder', $ThemeInfo, '');
   $ThemeVersion = ArrayValue('Version', $ThemeInfo, '');
   $ThemeUrl = ArrayValue('Url', $ThemeInfo, '');
   $Author = ArrayValue('Author', $ThemeInfo, '');
   $AuthorUrl = ArrayValue('AuthorUrl', $ThemeInfo, '');   
   $Active = $ThemeFolder == $this->EnabledThemeFolder;
   $Alt = $Alt ? FALSE : TRUE;
   $CssClass = $Alt ? ' Alt ' : '';
   $CssClass .= $Active ? ' Enabled' : ' Disabled';
   ?>
   <tr class="More<?php echo $CssClass != '' ? ' '.$CssClass : ''; ?>">
      <th><?php echo $ThemeName; ?></th>
      <td class="Alt"><?php echo ArrayValue('Description', $ThemeInfo, '&nbsp;'); ?></td>
   </tr>
   <tr class="<?php echo $CssClass != '' ? ' '.$CssClass : ''; ?>">
      <td class="Info">
         <?php
         if($Active) {
            echo Translate('Active');
         } else {
            echo Anchor('Apply', 'garden/settings/themes/'.$ThemeFolder.'/'.$Session->TransientKey(), '', array('target' => '_top'));
            echo '<span>|</span>';
            echo Anchor('Preview', 'garden/settings/previewtheme/'.$ThemeFolder, '', array('target' => '_top'));
         }
         ?>
      </td>
      <td class="Info Alt">
      <?php
         $RequiredApplications = ArrayValue('RequiredApplications', $ThemeInfo, FALSE);
         $Info = '';
         if ($ThemeVersion != '')
            $Info = sprintf(Translate('Version %s'), $ThemeVersion);
            
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
         
         if ($ThemeUrl != '') {
            $Info .= '<span>|</span>';
            $Info .= Anchor('Visit Theme Site', $ThemeUrl);
         }
         
         echo $Info != '' ? $Info : '&nbsp;';
      ?>
      </td>
   </tr>
   <?php
}
?>
   </tbody>
</table>
<?php
   printf(Translate('AddonProblems'), '<p class="Warning">'.PATH_CONF.DS.'config.php'.'</p>');