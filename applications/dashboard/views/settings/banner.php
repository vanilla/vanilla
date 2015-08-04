<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
?>
    <style>
        .Row {
            margin: 0;
            padding: 0;
            overflow: hidden;
        }

        .Column {
            margin: 0;
            overflow: hidden;
            float: left;
            display: inline;
        }

        .Grid_50 {
            width: 50%;
        }

        .Buttons {
            margin: 20px;
            text-align: right;
        }
    </style>

    <div class="Help Aside">
        <?php
        echo '<h2>', t('Need More Help?'), '</h2>';
        echo '<ul>';
        echo wrap(Anchor(t("Video tutorial on managing appearance"), 'settings/tutorials/appearance'), 'li');
        echo '</ul>';
        ?>
    </div>
    <h1><?php echo t('Banner'); ?></h1>
    <div class="PageInfo">
        <h2><?php echo t('Heads up!'); ?></h2>

        <p>
            <?php
            echo t('Spend a little time thinking about how you describe your site here.',
                'Spend a little time thinking about how you describe your site here. Giving your site a meaningful title and concise description could help your position in search engines.');
            ?>
        </p>
    </div>

<?php
echo $this->Form->open(array('enctype' => 'multipart/form-data'));
echo $this->Form->errors();
?>
    <div class="Row">
        <div class="Column Grid_50">
            <ul>
                <li>
                    <?php
                    echo $this->Form->label('Homepage Title', 'Garden.HomepageTitle');
                    echo wrap(
                        t('The homepage title is displayed on your home page.', 'The homepage title is displayed on your home page. Pick a title that you would want to see appear in search engines.'),
                        'div',
                        array('class' => 'Info')
                    );
                    echo $this->Form->textBox('Garden.HomepageTitle');
                    ?>
                </li>
                <li>
                    <?php
                    echo $this->Form->label('Site Description', 'Garden.Description');
                    echo wrap(
                        t("The site description usually appears in search engines.", 'The site description usually appears in search engines. You should try having a description that is 100–150 characters long.'),
                        'div',
                        array('class' => 'Info')
                    );
                    echo $this->Form->textBox('Garden.Description', array('Multiline' => TRUE));
                    ?>
                </li>
                <li>
                    <?php
                    echo $this->Form->label('Banner Title', 'Garden.Title');
                    echo wrap(
                        t("The banner title appears on your site's banner and in your browser's title bar.",
                            "The banner title appears on your site's banner and in your browser's title bar. It should be less than 20 characters. If a banner logo is uploaded, it will replace the banner title on user-facing forum pages. Also, keep in mind some themes may also hide this title."),
                        'div',
                        array('class' => 'Info')
                    );
                    echo $this->Form->textBox('Garden.Title');
                    ?>
                </li>
            </ul>
        </div>
        <div class="Column Grid_50">
            <ul>
                <li>
                    <?php
                    echo $this->Form->label('Banner Logo', 'Logo');
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
                        echo wrap(Anchor(t('Remove Banner Logo'), '/dashboard/settings/removelogo/'.$Session->TransientKey(), 'SmallButton'), 'div', array('style' => 'padding: 10px 0;'));
                        echo wrap(
                            t('LogoBrowse', 'Browse for a new banner logo if you would like to change it:'),
                            'div',
                            array('class' => 'Info')
                        );
                    }

                    echo $this->Form->Input('Logo', 'file');
                    ?>
                </li>
                <li>
                    <?php
                    echo $this->Form->label('Mobile Banner Logo', 'MobileLogo');
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
                        echo wrap(Anchor(t('Remove Mobile Banner Logo'), '/dashboard/settings/removemobilelogo/'.$Session->TransientKey(), 'SmallButton'), 'div', array('style' => 'padding: 10px 0;'));
                        echo wrap(
                            t('MobileLogoBrowse', 'Browse for a new mobile banner logo if you would like to change it:'),
                            'div',
                            array('class' => 'Info')
                        );
                    }

                    echo $this->Form->Input('MobileLogo', 'file');
                    ?>
                </li>
                <li>
                    <?php
                    echo $this->Form->label('Favicon', 'Favicon');
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
                        echo wrap(Anchor(t('Remove Favicon'), '/dashboard/settings/removefavicon/'.$Session->TransientKey(), 'SmallButton'), 'div', array('style' => 'padding: 10px 0;'));
                        echo wrap(
                            t('FaviconBrowse', 'Browse for a new favicon if you would like to change it:'),
                            'div',
                            array('class' => 'Info')
                        );
                    } else {
                        echo wrap(
                            t('FaviconDescription', "The shortcut icon that shows up in your browser's bookmark menu (16x16 px)."),
                            'div',
                            array('class' => 'Info')
                        );
                    }
                    echo $this->Form->Input('Favicon', 'file');
                    ?>
                </li>
                <li>
                    <?php
                    echo $this->Form->label('Share Image', 'ShareImage');
                    echo wrap(
                        t('ShareImageDescription', "When someone shares a link from your site we try and grab an image from the page. If there isn't an image on the page then we'll use this image instead. The image should be at least 50&times;50, but we recommend 200&times;200."),
                        'div',
                        array('class' => 'Info')
                    );
                    $ShareImage = $this->data('ShareImage');
                    if ($ShareImage) {
                        echo wrap(
                            img(Gdn_Upload::url($ShareImage), array('style' => 'max-width: 300px')),
                            'div'
                        );
                        echo wrap(Anchor(t('Remove Image'), '/dashboard/settings/removeshareimage', 'SmallButton Hijack'), 'div', array('style' => 'padding: 10px 0;'));
                        echo wrap(
                            t('FaviconBrowse', 'Browse for a new favicon if you would like to change it:'),
                            'div',
                            array('class' => 'Info')
                        );
                    }
                    echo $this->Form->Input('ShareImage', 'file');
                    ?>
                </li>
            </ul>
        </div>
    </div>
<?php

echo '<div class="Buttons">'.$this->Form->button('Save').'</div>';

echo $this->Form->close();
