<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
$desc = t('Spend a little time thinking about how you describe your site here.',
    'Spend a little time thinking about how you describe your site here. Giving your site a meaningful title and concise description could help your position in search engines.');
helpAsset(t('Heads up!'), $desc);
helpAsset(t('Need More Help?'), anchor(t("Video tutorial on managing appearance"), 'settings/tutorials/appearance'));
?>
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
                <li class="form-group">
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
                <li class="form-group">
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
                <li class="form-group">
                    <div class="label-wrap">
                    <?php
                    echo '<div>'.t('Banner Title').'</div>';
                    echo wrap(
                        t("The banner title appears on your site's banner and in your browser's title bar.",
                            "The banner title appears on your site's banner and in your browser's title bar. It should be less than 20 characters. If a banner logo is uploaded, it will replace the banner title on user-facing forum pages. Also, keep in mind some themes may hide this title."),
                        'div',
                        array('class' => 'Info')
                    ); ?>
                    </div>
                    <div class="input-wrap">
                    <?php echo $this->Form->textBox('Garden.Title'); ?>
                    </div>
                </li>
                <?php echo $this->Form->imageUploadPreview(
                    'Logo',
                    t('Banner Logo'),
                    t('LogoDescription', 'The banner logo appears at the top of your site. Some themes may not display this logo.'),
                    $this->data('Logo'),
                    '/dashboard/settings/removelogo',
                    t('Remove Banner Logo'),
                    t('Are you sure you want to delete your banner logo?')
                ); ?>
                <?php echo $this->Form->imageUploadPreview(
                    'MobileLogo',
                    t('Mobile Banner Logo'),
                    t('MobileLogoDescription', 'The mobile banner logo appears at the top of your site. Some themes may not display this logo.'),
                    $this->data('MobileLogo'),
                    '/dashboard/settings/removemobilelogo',
                    t('Remove Mobile Banner Logo'),
                    t('Are you sure you want to delete your mobile banner logo?')
                ); ?>
                <?php echo $this->Form->imageUploadPreview(
                    'Favicon',
                    t('Favicon'),
                    t('FaviconDescription', "Your site's favicon appears in your browser's title bar. It will be scaled to 16x16 pixels."),
                    $this->data('Favicon'),
                    '/dashboard/settings/removefavicon',
                    t('Remove Favicon'),
                    t('Are you sure you want to delete your favicon?')
                ); ?>
                <?php echo $this->Form->imageUploadPreview(
                    'ShareImage',
                    t('Share Image'),
                    t('ShareImageDescription', "When someone shares a link from your site we try and grab an image from the page. If there isn't an image on the page then we'll use this image instead. The image should be at least 50&times;50, but we recommend 200&times;200."),
                    $this->data('ShareImage'),
                    '/dashboard/settings/removeshareimage',
                    t('Remove Share Image'),
                    t('Are you sure you want to delete your share image?')
                ); ?>
            </ul>
        </div>
    </div>
<?php echo $this->Form->close('Save');
