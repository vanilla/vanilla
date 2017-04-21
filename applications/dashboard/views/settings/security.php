<?php if (!defined('APPLICATION')) exit();
helpAsset(t('Need More Help?'), anchor(t("Video tutorial on advanced settings"), 'settings/tutorials/category-management-and-advanced-settings'));
?>
<h1><?php echo t('Security'); ?></h1>
<?php
/** @var Gdn_Form $form */
$form = $this->Form;
echo $form->open();
echo $form->errors();
?>
    <ul>
        <li class="form-group">
            <?php
            $leavingLabel = 'Warn users if a link in a post will cause them to leave the forum';
            $leavingDesc = 'Alert users if they click a link in a post that will lead them away from the forum. ';
            $leavingDesc .= 'Users will not be warned when following links that match a Trusted Domain.';
            echo $form->toggle('Garden.Format.WarnLeaving', $leavingLabel, [], $leavingDesc);
            ?>
        </li>
        <li class="form-group">
            <div class="label-wrap">
            <?php echo $form->label('Trusted Domains', 'Garden.TrustedDomains'); ?>
                <div class="info">
                    <p>
                    <?php
                    echo t(
                        'You can specify a whitelist of trusted domains.',
                        'You can specify a whitelist of trusted domains (ex. yourdomain.com) that are safe for redirects and embedding.'
                    );
                    ?>
                    </p>
                    <p><strong><?php echo t('Note'); ?>:</strong> <?php echo t('Specify one domain per line. Use * for wildcard matches.'); ?></p>
                </div>
            </div>
            <div class="input-wrap">
            <?php echo $form->textBox('Garden.TrustedDomains', ['MultiLine' => true]); ?>
            </div>
        </li>
    </ul>
<?php echo $form->close('Save'); ?>
