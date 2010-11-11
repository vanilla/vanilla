<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
?>
<h1><?php echo $this->Data('Title'); ?></h1>
<div class="Info">
   <?php
   printf(
      T('Locales are in your %s folder.', "Locales allow you to support other languages on your site. Once a locale has been added to your %s folder, you can enable or disable it here."),
      '<code>'.PATH_ROOT.'/locales</code>'
   );
   ?>
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
         <th><?php echo T('Locale Name'); ?></th>
         <th><?php echo T('Description'); ?></th>
      </tr>
   </thead>
   <tbody>
<?php
$Alt = FALSE;
foreach ($this->Data('AvailableLocales') as $Key => $Info) {
   $ToggleText = $this->Data("EnabledLocales.$Key") ? 'Disable' : 'Enable';
   $RowClass = $this->Data("EnabledLocales.$Key") ? 'Enabled' : 'Disabled';
   if ($Alt) $RowClass .= ' Alt';
   ?>
   <tr class="More <?php echo $RowClass; ?>">
      <th><?php echo GetValue('Name', $Info, $Key); ?></th>
      <td class="Alt"><?php echo GetValue('Description', $Info, ''); ?></td>
   </tr>
   <tr class="<?php echo $RowClass; ?>">
      <td class="Info"><?php
         echo Anchor(
            T($ToggleText),
            '/settings/locales/'.strtolower($ToggleText).'/'.urlencode($Key).'/'.$Session->TransientKey(),
            $ToggleText . 'Addon SmallButton'
         );
      ?></td>
      <td class="Alt Info"><?php
         $RequiredApplications = GetValue('RequiredApplications', $Info, FALSE);
         $RequiredPlugins = GetValue('RequiredPlugins', $Info, FALSE);

         $InfoItems = ArrayTranslate($Info, array('Locale' => T('_Locale'), 'Version' => T('Version')));
         $InfoString = ImplodeAssoc(': ', '<span>|</span>', $InfoItems);

//            if (is_array($RequiredApplications) || is_array($RequiredPlugins)) {
//               if ($Info != '')
//                  $Info .= '<span>|</span>';
//
//               $Info .= T('Requires: ');
//            }

//            $i = 0;
//            if (is_array($RequiredApplications)) {
//               if ($i > 0)
//                  $Info .= ', ';
//
//               foreach ($RequiredApplications as $RequiredApplication => $VersionInfo) {
//                  $Info .= sprintf(T('%1$s Version %2$s'), $RequiredApplication, $VersionInfo);
//                  ++$i;
//               }
//            }
//
//            if ($RequiredPlugins !== FALSE) {
//               foreach ($RequiredPlugins as $RequiredPlugin => $VersionInfo) {
//                  if ($i > 0)
//                     $Info .= ', ';
//
//                  $Info .= sprintf(T('%1$s Version %2$s'), $RequiredPlugin, $VersionInfo);
//                  ++$i;
//               }
//            }

         if ($Author = GetValue('Author', $Info)) {
            $InfoString .= '<span>|</span>';
            $InfoString .= sprintf('By %s', Anchor($Author, GetValue('AuthorUrl', $Info, '')));
         }

         echo $InfoString != '' ? $InfoString : '&nbsp;';

      ?></td>
   </tr>
   <?php
}
?>
   </tbody>
</table>