<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
?>
    <?php Gdn_Theme::assetBegin('Help'); ?>
    <h2><?php echo t('Heads up!'); ?></h2>
    <div>
        <?php
        echo t('Spend a little time thinking about how you describe your site here.',
            'Spend a little time thinking about how you describe your site here. Giving your site a meaningful title and concise description could help your position in search engines.');
        ?>
    </div>
    <div class="Help Aside">
        <?php
        echo '<h2>', t('Need More Help?'), '</h2>';
        echo '<ul>';
        echo wrap(Anchor(t("Video tutorial on managing appearance"), 'settings/tutorials/appearance'), 'li');
        echo '</ul>';
        ?>
    </div>
    <?php Gdn_Theme::assetEnd(); ?>
<div class="header-block">
    <h1><?php echo t('Banner'); ?></h1>
</div>
<?php
echo $this->Form->open(array('enctype' => 'multipart/form-data'));
echo $this->Form->errors();
?>
    <div class="Row">
        <div>
            <ul>
                <li class="form-group row">
                    <div class="label-wrap">
                    <?php
                    echo '<div>'.t('Homepage Title').'</div>';
                    echo wrap(
                        t('The homepage title is displayed on your home page.', 'The homepage title is displayed on your home page. Pick a title that you would want to see appear in search engines.'),
                        'div',
                        array('class' => 'Info')
                    ); ?>
                    </div>
                    <div class="input-wrap">
                    <?php echo $this->Form->textBox('Garden.HomepageTitle'); ?>
                    </div>
                </li>
                <li class="form-group row">
                    <div class="label-wrap">
                    <?php
                    echo '<div>'.t('Site Description').'</div>';
//                    echo $this->Form->label('Site Description', 'Garden.Description');
                    echo wrap(
                        t("The site description usually appears in search engines.", 'The site description usually appears in search engines. You should try having a description that is 100–150 characters long.'),
                        'div',
                        array('class' => 'Info')
                    ); ?>
                    </div>
                    <div class="input-wrap">
                    <?php echo $this->Form->textBox('Garden.Description', array('Multiline' => TRUE)); ?>
                    </div>
                </li>
                <li class="form-group row">
                    <div class="label-wrap">
                    <?php
                    echo '<div>'.t('Banner Title').'</div>';
//                    echo $this->Form->label('Banner Title', 'Garden.Title');
                    echo wrap(
                        t("The banner title appears on your site's banner and in your browser's title bar.",
                            "The banner title appears on your site's banner and in your browser's title bar. It should be less than 20 characters. If a banner logo is uploaded, it will replace the banner title on user-facing forum pages. Also, keep in mind some themes may also hide this title."),
                        'div',
                        array('class' => 'Info')
                    ); ?>
                    </div>
                    <div class="input-wrap">
                    <?php echo $this->Form->textBox('Garden.Title'); ?>
                    </div>
                </li>
                <li class="form-group row">
                    <div class="label-wrap">
                    <?php
                    echo '<div>'.t('Banner Logo').'</div>';
                    echo wrap(
                        t('LogoDescription', 'The banner logo appears at the top of your site. Some themes may not display this logo.'),
                        'div',
                        array('class' => 'Info')
                    );

                    $Logo = $this->data('Logo');
                    if ($Logo) {
                        echo wrap(
                            img(Gdn_Upload::url($Logo)),
                            'div'
                        );
                        echo wrap(Anchor(t('Remove Banner Logo'), '/dashboard/settings/removelogo/'.$Session->TransientKey(), 'SmallButton'), 'div');
                    } ?>
                    </div>
                    <div class="input-wrap">
                    <?php echo $this->Form->fileUpload('Logo'); ?>
                    </div>
                </li>
                <li class="form-group row">
                    <div class="label-wrap">
                    <?php
                    echo '<div>'.t('Mobile Banner Logo').'</div>';
                    echo wrap(
                        t('MobileLogoDescription', 'The mobile banner logo appears at the top of your site. Some themes may not display this logo.'),
                        'div',
                        array('class' => 'Info')
                    );

                    $MobileLogo = $this->data('MobileLogo');
                    if ($MobileLogo) {
                        echo wrap(
                            img(Gdn_Upload::url($MobileLogo)),
                            'div'
                        );
                        echo wrap(Anchor(t('Remove Mobile Banner Logo'), '/dashboard/settings/removemobilelogo/'.$Session->TransientKey(), 'SmallButton'), 'div');
                    } ?>
                    </div>
                    <div class="input-wrap">
                    <?php echo $this->Form->fileUpload('MobileLogo'); ?>
                    </div>
                </li>
                <li class="form-group row">
                    <div class="label-wrap">
                    <?php
                    echo '<div>'.t('Favicon').'</div>';
                    echo wrap(
                        t('FaviconDescription', "Your site's favicon appears in your browser's title bar. It will be scaled to 16x16 pixels."),
                        'div',
                        array('class' => 'Info')
                    );
                    $Favicon = $this->data('Favicon');
                    if ($Favicon) {
                        echo wrap(
                            img(Gdn_Upload::url($Favicon)),
                            'div'
                        );
                        echo wrap(Anchor(t('Remove Favicon'), '/dashboard/settings/removefavicon/'.$Session->TransientKey(), 'SmallButton'), 'div');
                    }
                    ?>
                    </div>
                    <div class="input-wrap">
                    <?php echo $this->Form->fileUpload('Favicon'); ?>
                    </div>
                </li>
                <li class="form-group row">
                    <div class="label-wrap">
                    <?php
                    echo '<div>'.t('Share Image').'</div>';
                    echo wrap(
                        t('ShareImageDescription', "When someone shares a link from your site we try and grab an image from the page. If there isn't an image on the page then we'll use this image instead. The image should be at least 50&times;50, but we recommend 200&times;200."),
                        'div',
                        array('class' => 'Info')
                    );
                    $ShareImage = $this->data('ShareImage');
                    if ($ShareImage) {
                        echo wrap(
                            img(Gdn_Upload::url($ShareImage)),
                            'div'
                        );
                        echo wrap(Anchor(t('Remove Image'), '/dashboard/settings/removeshareimage', 'SmallButton Hijack'), 'div');
                    } ?>
                    </div>
                    <div class="input-wrap">
                    <?php echo $this->Form->fileUpload('ShareImage');?>
                    </div>
                </li>
            </ul>
        </div>
    </div>
<?php

echo '<div class="form-footer js-modal-footer">'.$this->Form->button('Save').'</div>';

echo $this->Form->close();
