<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
?>
<h1><?php echo $this->data('Title'); ?></h1>
<?php
if ($this->data('DefaultLocaleWarning')) {
    echo '<div class="Errors">', sprintf(t('Your default locale won\'t display properly', 'Your default locale won\'t display properly until it is enabled below. Please enable the following: %s.'), $this->data('MatchingLocalePacks')), '</div>';
}
echo $this->Form->open();
echo $this->Form->errors(); ?>
<div class="form-group row">
    <?php echo
    '<div class="label-wrap">', t('Default Locale'), '</div> ',
    '<div class="input-wrap input-wrap-multiple">',
    $this->Form->DropDown('Locale', $this->data('Locales')),
    $this->Form->button('Save', array('style' => 'margin-bottom: 0px')),
    '</div>';
    ?>
</div>
<?php
echo $this->Form->close();
?>
<?php echo $this->Form->errors();
$this->addonType = 'locales';
include_once PATH_APPLICATIONS.'/dashboard/views/settings/plugins.php';
?>

<?php Gdn_Theme::assetBegin('Help'); ?>
<div class="Help Aside">
    <?php
    echo '<h2>', t('Need More Help?'), '</h2>';
    echo '<ul>';
    echo '<li>', anchor(t('Internationalization & Localization'), 'http://docs.vanillaforums.com/developers/locales/'), '</li>';
    echo '</ul>';
    ?>
</div>
<?php Gdn_Theme::assetEnd(); ?>
