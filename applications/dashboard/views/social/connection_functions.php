<?php

/**
 *
 *
 * @param $Connection
 */
function writeConnection($Connection, $wrap = true) {
    $c = Gdn::controller();
    $Enabled = val('Enabled', $Connection);
    $SettingsUrl = val('SettingsUrl', $Connection);

    $Css = array();
    $Css[] = $Enabled ? 'Enabled' : 'Disabled';

    $CssClasses = implode(' ', $Css);

    $Addendums = array();

    $Configured = val('Configured', $Connection);
    if (!$Configured) {
        $Addendums[] = wrap(t('not configured'), 'span', array('class' => 'NotConfigured'));
    }

    $Index = val('Index', $Connection, val('ProviderKey', $Connection));

    ?>
    <?php if($wrap) { ?>
    <li id="<?php echo "Provider_$Index"; ?>" class="Item <?php echo $CssClasses; ?>">
        <?php } ?>
        <span class="IconWrap">
            <?php echo img(asset(val('Icon', $Connection, '/applications/dashboard/design/images/connection-64.png'), '//')); ?>
        </span>
        <span class="Connection-Info">
            <div class="Connection-Name">
                <?php
                if ($Enabled && !empty($SettingsUrl)) {
                    echo anchor(val('Name', $Connection, t('Unknown')), $SettingsUrl);
                } else {
                    echo val('Name', $Connection, t('Unknown'));
                }
                ?>

                <?php if (sizeof($Addendums)): ?>
                    <span class="Addendums">
                    <?php echo implode(', ', $Addendums); ?>
                    </span>
                <?php endif; ?>
            </div>
            <span class="Connection-Description"><?php echo val('Description', $Connection, t('Unknown')); ?></span>
         </span>
        <span class="Connection-Buttons">
            <?php
            if ($Enabled) {
                if (!empty($SettingsUrl)) {
                    echo anchor(sprite('SpOptions'), "/social/{$Connection['Index']}", 'Connection-Configure').' ';
                }
                $SliderState = 'Active';
                $toggleState = 'on';
                echo wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', "/social/disable/$Index", 'Hijack', ['aria-label' =>sprintf(t('Disable %s'), val('Name', $Connection))]), 'span', array('class' => "toggle-wrap toggle-wrap-{$toggleState} ActivateSlider-{$SliderState}"));
            } else {
                $SliderState = 'InActive';
                $toggleState = 'off';
                echo wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', "/social/enable/$Index", 'Hijack', ['aria-label' =>sprintf(t('Enable %s'), val('Name', $Connection))]), 'span', array('class' => "toggle-wrap toggle-wrap-{$toggleState} ActivateSlider-{$SliderState}"));
            }
            ?>
        </span>
    <?php if($wrap) { ?>
        </li>
    <?php
    }
}
