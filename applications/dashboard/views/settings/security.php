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
                    <p><?php echo t('Specify one domain per line. Use * for wildcard matches.'); ?></p>
                </div>
            </div>
            <div class="input-wrap">
            <?php echo $form->textBox('Garden.TrustedDomains', ['MultiLine' => true]); ?>
            </div>
        </li>
    </ul>
    <h2 class="subheading"><?php echo t('HTTP Strict Transport Security (HSTS) Settings'); ?></h2>
    <ul>
        <li>
            <div class="info"><?php echo sprintf(t('Learn more about HSTS at %s.'), '<a href="https://hstspreload.org/">https://hstspreload.org/</a>'); ?></div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <?php echo $form->label('Max Age', 'Garden.Security.Hsts.MaxAge'); ?>
                <div class="info">
                    <p><?php echo t(
                            'Security.Hsts.MaxAgeRecommendation',
                            'We recommend starting with a max age of 1 week'.
                                ' and then increasing it to 1 month then 1 year once you see your site works as expected.'
                        ); ?>
                    </p>
                </div>
            </div>
            <div class="input-wrap inline">
                <?php echo $form->radioList('Garden.Security.Hsts.MaxAge',
                    [
                        604800 => plural(1, '%s week', '%s weeks'),
                        2592000 => plural(1, '%s month', '%s months'),
                        31536000 => plural(1, '%s year', '%s years'),
                        63072000 => plural(2, '%s year', '%s years')
                    ],
                    ['class' => 'inline']
                ); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap-wide">
                <?php echo $form->label('Include Subdomains', 'Garden.Security.Hsts.IncludeSubDomains'); ?>
                <div class="info">
                    <p>
                        <?php
                        echo t(
                            'Security.Hsts.IncludeSubDomains',
                            'When enabled, this rule applies to all of your site\'s subdomains as well.'
                        );
                        ?>
                    </p>
                    <p><?php echo t('Security.Hsts.HTTPSWarning', 'Warning: Only enable this feature if you are sure that all of your subdomains are configured for HTTPS with valid certificates.'); ?></p>
                </div>
            </div>
            <div class="input-wrap-right">
                <?php echo $form->toggle('Garden.Security.Hsts.IncludeSubDomains'); ?>
            </div>
        </li>

        <li class="form-group">
            <div class="label-wrap-wide">
                <?php echo $form->label('Preload', 'Garden.Security.Hsts.Preload'); ?>
                <div class="info">
                    <p class="warning">
                        <?php echo t(
                                'Security.Hsts.SubmitWarning',
                                'Warning: It\'s great to support HSTS preloading as a best practice. However, you must submit your site to hstspreload.org '.
                                    'to ensure that it is successfully pre-loaded (i.e. to get the full protection for the intended configuration).'
                        ); ?></p>
                </div>
            </div>
            <div class="input-wrap-right">
                <?php echo $form->toggle('Garden.Security.Hsts.Preload'); ?>
            </div>
        </li>
    </ul>
<?php echo $form->close('Save'); ?>
