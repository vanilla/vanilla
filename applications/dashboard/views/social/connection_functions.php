<?php if (!defined('APPLICATION')) exit();

function WriteConnection($Connection) {
   $c = Gdn::Controller();
   $Enabled = GetValue('Enabled', $Connection);
   $SettingsUrl = GetValue('SettingsUrl', $Connection);
   
   $Css = array();
   $Css[] = $Enabled ? 'Enabled' : 'Disabled';
   
   $CssClasses = implode(' ', $Css);
   
   $Addendums = array();
   
//   $RequiresRegistration = GetValue('RequiresRegistration', $Connection);
//   if ($RequiresRegistration)
//      $Addendums[] = Wrap(T('requires registration'), 'span', array('class' => 'RequiresRegistration'));
   
   $Configured = GetValue('Configured', $Connection);
   if (!$Configured)
      $Addendums[] = Wrap(T('not configured'), 'span', array('class' => 'NotConfigured'));
   
   $Index = GetValue('Index', $Connection, GetValue('ProviderKey', $Connection));
   
?>
   <li id="<?php echo "Provider_$Index"; ?>" class="Item <?php echo $CssClasses; ?>">
      <div class="Connection-Header">
         <span class="IconWrap">
            <?php echo Img(Asset(GetValue('Icon', $Connection,'/applications/dashboard/design/images/connection-64.png'), '//')); ?>
         </span>
         <span class="Connection-Info">
            <span class="Connection-Name">
               <?php 
                  if ($Enabled)
                     echo Anchor(GetValue('Name', $Connection, T('Unknown')), $SettingsUrl);
                  else
                     echo GetValue('Name', $Connection, T('Unknown'));
               ?>
               
               <?php if (sizeof($Addendums)): ?>
                  <span class="Addendums">
                  <?php echo implode(', ', $Addendums); ?>
                  </span>
               <?php endif; ?>
            </span>
            <span class="Connection-Description"><?php echo GetValue('Description', $Connection, T('Unknown')); ?></span>
         </span>
         <span class="Connection-Buttons">
            <?php
            if ($Enabled) {
               echo Anchor(Sprite('SpOptions'), "/social/{$Connection['Index']}", 'Connection-Configure').' ';
               $SliderState = 'Active';
               echo Wrap(Anchor(T('Enabled'), "/social/disable/$Index", 'Hijack SmallButton'), 'span', array('class' => "ActivateSlider ActivateSlider-{$SliderState}"));
            } else {
               $SliderState = 'InActive';
               echo Wrap(Anchor(T('Disabled'), "/social/enable/$Index", 'Hijack SmallButton'), 'span', array('class' => "ActivateSlider ActivateSlider-{$SliderState}"));
            }
         ?></span>
      </div>
   </li>
<?php
}