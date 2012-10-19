<?php if (!defined('APPLICATION')) exit();

function WriteConnection($Connection) {
   $c = Gdn::Controller();
   $Enabled = GetValue('Enabled', $Connection);
   $SettingsUrl = GetValue('SettingsUrl', $Connection);
   
   $Css = array();
   $Css[] = $Enabled ? 'Enabled' : 'Disabled';
   
   $CssClasses = implode(' ', $Css);
?>
   <li id="<?php echo "Provider_{$Connection['Name']}"; ?>" class="Item <?php echo $CssClasses; ?>">
      <div class="Connection-Header">
         <span class="IconWrap">
            <?php echo Img(Asset(GetValue('Icon', $Connection,'/applications/dashboard/design/images/connection-64.png'))); ?>
         </span>
         <span class="Connection-Name">
            <?php echo Anchor(GetValue('Name', $Connection, T('Unknown')), $SettingsUrl); ?>
            <span class="Connection-Description"><?php echo GetValue('Description', $Connection, T('Unknown')); ?></span>
         </span>
         <span class="Connection-Enable"><?php
            if ($Enabled)
               echo Anchor(T('Disable'), Url("/social/disable/{$Connections['Name']}"), 'Hijack SmallButton');
            else
               echo Anchor(T('Enable'), Url("/social/enable/{$Connections['Name']}"), 'Hijack SmallButton');
         ?></span>
      </div>
   </li>
<?php
}