<?php use Vanilla\Theme\BoxThemeShim;

if (!defined('APPLICATION')) exit();
    $dataDriven = \Gdn::themeFeatures()->useDataDrivenTheme();
?>
<div class="Box GuestBox">
    <?php BoxThemeShim::startHeading(); ?>
    <h4 class="GuestBox-title">
        <?php echo t('Howdy, Stranger!'); ?>
    </h4>
    <?php BoxThemeShim::endHeading(); ?>
    <?php BoxThemeShim::startBox(); ?>
    <p class="GuestBox-message">
        <?php echo t($this->MessageCode, $this->MessageDefault); ?>
    </p>

    <p class="GuestBox-beforeSignInButton">
        <?php $this->fireEvent('BeforeSignInButton'); ?>
    </p>

    <?php
    if ($this->data('signInUrl')) {
        echo '<div class="P GuestBox-buttons">';
        echo anchor(t('Sign In'), $this->data('signInUrl'), 'Button Primary'.(signInPopup() ? ' SignInPopup' : ''), ['rel' => 'nofollow', 'aria-label' => t("Sign In Now")]);

        if ($this->data('registerUrl')) {
            echo ' '.anchor(t('Register', t('Apply for Membership', 'Register')), $this->data('registerUrl'), 'Button ApplyButton', ['rel' => 'nofollow', 'aria-label' => t("Register Now")]);
        }

        echo '</div>';
    }
    ?>
    <?php $this->fireEvent('AfterSignInButton'); ?>
    <?php BoxThemeShim::endBox(); ?>
</div>
