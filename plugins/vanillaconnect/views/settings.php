<?php if (!defined('APPLICATION')) exit();
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
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
?>
</section>
<div class="table-wrap">
    <table class="table-data js-tj">
        <thead>
        <tr>
            <th><?php echo t('Client ID'); ?></th>
            <th><?php echo t('Provider Name'); ?></th>
            <th class="column-lg"><?php echo t('Sign In URL'); ?></th>
            <th class="column-xs"><?php echo t('Trusted'); ?></th>
            <th class="column-xs"><?php echo t('Auto link'); ?></th>
            <th class="column-xs"></th>
        </tr>
        </thead>
        <tbody>
        <?php
        /** @var \Vanilla\VanillaConnect\VanillaConnectAuthenticator $authenticator */
        foreach ($this->data('authenticators') as $authenticator):
        ?>
            <tr>
                <td><?php echo htmlspecialchars($authenticator->getClientID()); ?></td>
                <td><?php echo htmlspecialchars($authenticator->getName()); ?></td>
                <td><?php echo htmlspecialchars($authenticator->getSignInUrl()); ?></td>
                <td><?php echo $authenticator->isTrusted() ? t('Yes') : t('No') ?></td>
                <td><?php echo $authenticator->canAutoLinkUser() ? t('Yes') : t('No') ?></td>
                <td class="options">
                    <div class="btn-group">
                        <?php
                        echo anchor(dashboardSymbol('edit'), '/settings/vanillaconnect/addedit?authenticatorID='.urlencode($authenticator->getID()), 'js-modal btn btn-icon', ['aria-label' => t('Edit'), 'title' => t('Edit')]);
                        echo anchor(dashboardSymbol('delete'), '/settings/vanillaconnect/delete?authenticatorID='.urlencode($authenticator->getID()), 'js-modal-confirm btn btn-icon', ['aria-label' => t('Delete'), 'title' => t('Delete')]);
                        ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
