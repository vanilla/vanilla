<?php if (!defined('APPLICATION')) exit(); ?>
    <div class="Help Aside">
        <?php
        echo '<h2>', t('Need More Help?'), '</h2>';
        echo '<ul>';
        echo wrap(Anchor('Using OAuth 2.0 for Login (OpenID Connect)', 'https://developers.google.com/accounts/docs/OAuth2Login'), 'li');
        echo wrap(Anchor('Google Developers Console', 'https://console.developers.google.com/'), 'li');
        echo '</ul>';
        ?>
    </div>
    <h1><?php echo $this->data('Title'); ?></h1>
    <div class="Info">
        <?php echo anchor(t('How to set up Google+ Social Connect.'), 'http://docs.vanillaforums.com/addons/googleplus/', array('target' => '_blank')); ?>
    </div>
<?php
$Cf = $this->ConfigurationModule;

$Cf->render();
?>
