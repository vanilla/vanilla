<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php echo $this->data('Title'); ?></h1>
<div class="padded alert alert-warning">
    <?php echo sprintf(t('You must register your application with %s for this plugin to work.'), t('Google+')); ?>
</div>
<div class="padded">
    <?php echo anchor(sprintf(t('How to set up %s.'), t('Google+ Social Connect')), 'http://docs.vanillaforums.com/help/sso/social-connect/#google-plus', ['target' => '_blank']); ?>
</div>
<?php
$Cf = $this->ConfigurationModule;

$Cf->render();
?>
