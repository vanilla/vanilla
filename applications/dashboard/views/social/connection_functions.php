<?php if (!defined('APPLICATION')) exit();

function writeConnection($Connection) {
    $c = Gdn::controller();
    $Enabled = val('Enabled', $Connection);
    $SettingsUrl = val('SettingsUrl', $Connection);

    $Css = array();
    $Css[] = $Enabled ? 'Enabled' : 'Disabled';

    $CssClasses = implode(' ', $Css);

    $Addendums = array();

//   $RequiresRegistration = val('RequiresRegistration', $Connection);
//   if ($RequiresRegistration)
//      $Addendums[] = wrap(t('requires registration'), 'span', array('class' => 'RequiresRegistration'));

    $Configured = val('Configured', $Connection);
    if (!$Configured)
        $Addendums[] = wrap(t('not configured'), 'span', array('class' => 'NotConfigured'));

    $Index = val('Index', $Connection, val('ProviderKey', $Connection));

    ?>
    <li id="<?php echo "Provider_$Index"; ?>" class="Item <?php echo $CssClasses; ?>">
        <div class="Connection-Header">
         <span class="IconWrap">
            <?php echo img(Asset(val('Icon', $Connection, '/applications/dashboard/design/images/connection-64.png'), '//')); ?>
         </span>
         <span class="Connection-Info">
            <span class="Connection-Name">
               <?php
               if ($Enabled)
                   echo anchor(val('Name', $Connection, t('Unknown')), $SettingsUrl);
               else
                   echo val('Name', $Connection, t('Unknown'));
               ?>

                <?php if (sizeof($Addendums)): ?>
                    <span class="Addendums">
                  <?php echo implode(', ', $Addendums); ?>
                  </span>
                <?php endif; ?>
            </span>
            <span
                class="Connection-Description"><?php echo val('Description', $Connection, t('Unknown')); ?></span>
         </span>
         <span class="Connection-Buttons">
            <?php
            if ($Enabled) {
                echo anchor(sprite('SpOptions'), "/social/{$Connection['Index']}", 'Connection-Configure').' ';
                $SliderState = 'Active';
                echo wrap(Anchor(t('Enabled'), "/social/disable/$Index", 'Hijack SmallButton'), 'span', array('class' => "ActivateSlider ActivateSlider-{$SliderState}"));
            } else {
                $SliderState = 'InActive';
                echo wrap(Anchor(t('Disabled'), "/social/enable/$Index", 'Hijack SmallButton'), 'span', array('class' => "ActivateSlider ActivateSlider-{$SliderState}"));
            }
            ?></span>
        </div>
    </li>
<?php
}
