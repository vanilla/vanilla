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
    <li id="<?php echo "Provider_$Index"; ?>" class="media <?php echo $CssClasses; ?>">
        <?php } ?>
        <div class="media-left">
            <div class="image-wrap IconWrap">
                <?php echo img(asset(val('Icon', $Connection, '/applications/dashboard/design/images/connection-64.png'), '//')); ?>
            </div>
        </div>
        <div class="media-body">
            <div class="media-title">
                <?php echo val('Name', $Connection, t('Unknown')); ?>
                <?php if (sizeof($Addendums)): ?>
                    <div class="info">
                    <?php echo implode(', ', $Addendums); ?>
                    </div>
                <?php endif; ?>
            </div>
            <span class="media-description"><?php echo val('Description', $Connection, t('Unknown')); ?></span>
         </div>
        <div class="media-right media-options">
            <div class="btn-group">
            <?php
            if ($Enabled && !empty($SettingsUrl)) {
                echo anchor('<span class="icon icon-edit">', $SettingsUrl, 'btn btn-secondary', ['aria-label' => sprintf(t('Settings for %s'), val('Name', $Connection, t('Unknown')))]);
            } ?>
            </div>
            <div class="toggle">
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
            </div>
        </div>
    <?php if($wrap) { ?>
        </li>
    <?php
    }
}
