<?php if (!defined('APPLICATION')) exit();
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

// TODO when the doc is created.
//$links = '<ul>';
//$links .= '<li>'.anchor(t('VanillaConnect Documentation'), 'http://docs.vanillaforums.com/features/sso/vanillaconnect/').'</li>';
//$links .= '<li>'.anchor(t('VanillaConnect Client Libraries'), '').'</li>';
//$links .= '</ul>';

helpAsset(sprintf(t('About %s'), 'VanillaConnect'), t('You can connect to multiple sites that support VanillaConnect.'));
//helpAsset(t('Need More Help?'), $links);

echo heading(sprintf(t('%s Settings'), 'VanillaConnect'), t('Add Provider'), '/settings/vanillaconnect/addedit', 'btn btn-primary js-modal');
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
    <?php echo $this->Form->close('Save'); ?>
</section>
<div class="table-wrap">
    <table class="table-data js-tj">
        <thead>
        <tr>
            <th><?php echo t('Client ID'); ?></th>
            <th><?php echo t('Provider Name'); ?></th>
            <th class="column-lg"><?php echo t('Sign In URL'); ?></th>
            <th class="column-xs"><?php echo t('Trusted'); ?></th>
            <th class="column-xs"><?php echo t('Default'); ?></th>
            <th class="column-xs"></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($this->data('providers') as $provider): ?>
            <tr>
                <td><?php echo htmlspecialchars($provider['AuthenticationKey']); ?></td>
                <td><?php echo htmlspecialchars($provider['Name']); ?></td>
                <td><?php echo htmlspecialchars($provider['SignInUrl']); ?></td>
                <td><?php echo $provider['Trusted'] ? t('Yes') : t('No') ?></td>
                <td><?php echo $provider['IsDefault'] ? t('Yes') : t('No') ?></td>
                <td class="options">
                    <div class="btn-group">
                        <?php
                        echo anchor(dashboardSymbol('edit'), '/settings/vanillaconnect/addedit?client_id='.urlencode($provider['AuthenticationKey']), 'js-modal btn btn-icon', ['aria-label' => t('Edit'), 'title' => t('Edit')]);
                        echo anchor(dashboardSymbol('delete'), '/settings/vanillaconnect/delete?client_id='.urlencode($provider['AuthenticationKey']), 'js-modal-confirm btn btn-icon', ['aria-label' => t('Delete'), 'title' => t('Delete')]);
                        ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
