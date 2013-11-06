<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
?>
<div class="Help Aside">
   <?php
   echo '<h2>', T('Need More Help?'), '</h2>';
   echo '<ul>';
   echo '<li>', Anchor(T('Enabling a Locale Pack'), 'http://vanillaforums.org/docs/Localization#Enabling'), '</li>';
   echo '<li>', Anchor(T('Internationalization & Localization'), 'http://vanillaforums.org/docs/Localization'), '</li>';
   echo '</ul>';
   ?>
</div>

<h1><?php echo $this->Data('Title'); ?></h1>
<div class="Info">
   <?php
   printf(
      T('Locales are in your %s folder.', "Locales allow you to support other languages on your site. Once a locale has been added to your %s folder, you can enable or disable it here."),
      '<code>'.PATH_ROOT.'/locales</code>'
   );

   if ($this->Data('DefaultLocaleWarning'))
      echo '<div class="Errors"><ul><li>', sprintf(T('Your default locale won\'t display properly', 'Your default locale won\'t display properly until it is enabled below. Please enable the following: %s.'), $this->Data('MatchingLocalePacks')), '</li></ul></div>';

   echo
      '<p>',
         $this->Form->Open(),
         $this->Form->Errors(),
         '<b>', T('Default Locale'), '</b> ',
         $this->Form->DropDown('Locale', $this->Data('Locales')),
         $this->Form->Button('Save', array('style' => 'margin-bottom: 0px')),
         ' ', $this->Form->Close(),
      '</p>';
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
   // Hide skeleton locale pack
   if ($Key == 'skeleton')
      continue;
      
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

         echo $InfoString != '' ? $InfoString : '&#160;';

      ?></td>
   </tr>
   <?php
}
?>
   </tbody>
</table>
