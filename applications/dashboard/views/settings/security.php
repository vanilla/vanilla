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
    <h2 class="subheading"><?php echo t('HTTP Strict Transport Security (HSTS) Directives'); ?></h2>
    <ul>
        <li>
            <div class="info">Learn more about hsts: <a href="https://hstspreload.org/">https://hstspreload.org/</a></div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <?php echo $form->label('Max-age', 'Garden.Security.Hsts.MaxAge'); ?>
                <div class="info">
                    <p><strong><?php echo t('Note'); ?>:</strong>
                        <?php echo t(
                            'We recommend to enable this directive as your first directive with a value of WEEK'
                                    . 'and then increase it to MONTH and YEAR once you see your site works as expected.'
                        ); ?>
                    </p>
                </div>
            </div>
            <div class="input-wrap inline">
                <?php echo $form->radioList('Garden.Security.Hsts.MaxAge',
                    [
                        604800 => t('Week'),
                        2592000 => t('Month'),
                        31536000 => t('Year'),
                        63072000 => t('2 Years')
                    ],
                    ['class' => 'inline']
                ); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap-wide">
                <?php echo $form->label('Include subdomains', 'Garden.Security.Hsts.IncludeSubDomains'); ?>
                <div class="info">
                    <p>
                        <?php
                        echo t(
                            'Security.Hsts.IncludeSubDomains',
                            'If this optional parameter is specified, this rule applies to all of the site\'s subdomains as well.'
                        );
                        ?>
                    </p>
                    <p><strong><?php echo t('Note'); ?>:</strong> <?php echo t('Enable this feature if you are sure that all your subdomains are configured for https and have valid certificates installed.'); ?></p>
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
                    <p class="warning"><strong><?php echo t('Warning'); ?>:</strong>
                        <?php echo t('It\'s great to support HSTS preloading as a best practice. However, you need to check requirements and submit your site to hstspreload.org to ensure that it is successfully preloaded (i.e. to get the full protection for the intended configuration).'); ?></p>
                </div>
            </div>
            <div class="input-wrap-right">
                <?php echo $form->toggle('Garden.Security.Hsts.Preload'); ?>
            </div>
        </li>
    </ul>
<?php echo $form->close('Save'); ?>
