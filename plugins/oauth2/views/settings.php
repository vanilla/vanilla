<?php
echo heading(sprintf(t('%s Settings'), 'OAuth2'), t('Add Connection'), '/settings/oauth2/addedit', 'btn btn-primary js-modal');
?>
<section>
    <?php
    echo subheading(t('Signing In'));
    echo $this->Form->open();
    echo $this->Form->errors(); ?>
<div class="form-group">
    <div class="label-wrap-wide">
        <?php echo t('Auto Connect'); ?>
        <?php echo '<div class="info">'.t('Automatically connect to an existing user account if it has the same email address.').'</div>' ?>
    </div>
    <div class="input-wrap-right">
        <?php echo $this->Form->toggle('Garden.Registration.AutoConnect'); ?>
    </div>
</div>
<div class="form-group">
    <div class="label-wrap-wide">
        <?php echo t('Use Popup Sign In Pages'); ?>
        <?php echo '<div class="info">'.t('Use popups for sign in pages (not recommended while using SSO).').'</div>'; ?>
    </div>
    <div class="input-wrap-right">
        <?php echo $this->Form->toggle('Garden.SignIn.Popup'); ?>
    </div>
</div>
<?php echo $this->Form->close('Save'); ?>
</section>
<div class="table-wrap">
    <table class="table-data js-tj">
        <thead>
        <tr>
            <th><?php echo t('Site Name'); ?></th>
            <th><?php echo t('Slug'); ?></th>
            <th><?php echo t('Client ID'); ?></th>
            <th class="column-sm"><?php echo t('Active') ?></th>
            <th class="column-sm"><?php echo t('Options') ?></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($this->Data('ProviderKeys') as $providerKey): ?>
            <?php $provider = Gdn_AuthenticationProviderModel::getProviderByKey($providerKey)?>
            <tr id="provider_<?php echo $provider['AuthenticationKey'] ?>">
                <td><?php echo htmlspecialchars($provider['Name']); ?></td>
                <td><?php echo htmlspecialchars($provider['AuthenticationKey']); ?></td>
                <td><?php echo htmlspecialchars($provider['AssociationKey']); ?></td>
                <td class="toggle-container">
                    <?php
                    if ($provider['Active']) {
                        $state = 'on';
                        $url = '/oauth2/state/'.$provider['AuthenticationKey'].'/disabled';
                    } else {
                        $state = 'off';
                        $url = '/oauth2/state/'.$provider['AuthenticationKey'].'/active';
                    }
                    echo wrap(
                        anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', $url, 'Hijack'),
                        'span',
                        ['class' => "toggle-wrap toggle-wrap-$state"]
                    );
                    ?>
                </td>
                <td class="options column-sm">
                    <div class="btn-group">
                        <?php
                        echo anchor(dashboardSymbol('edit'), '/settings/oauth2/addedit?connectionKey='.urlencode($provider['AuthenticationKey']), 'js-modal btn btn-icon', ['aria-label' => t('Edit'), 'title' => t('Edit')]);
                        echo anchor(dashboardSymbol('delete'), '/settings/oauth2/delete?connectionKey='.urlencode($provider['AuthenticationKey']), 'js-modal-confirm js-hijack btn btn-icon', ['aria-label' => t('Delete'), 'title' => t('Delete')]);
                        ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
