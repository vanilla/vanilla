<?php use Vanilla\Theme\BoxThemeShim;

if (!defined('APPLICATION')) exit();
if (!BoxThemeShim::isActive()) {
    if (Gdn::themeFeatures()->useProfileHeader()) {
        echo $this->fetchView("mobile-user-header");
        echo Gdn_Theme::module('ProfileOptionsModule');
    }
    echo '<div class="User" itemscope itemtype="http://schema.org/Person">';
    // If box theme shim is active, this renders in profile/index.php
    echo $this->fetchView('before-page-box');
}
$this->fireEvent('BeforeUserInfo');
echo Gdn_Theme::module('UserInfoModule');
$this->fireEvent('AfterUserInfo');

if (!BoxThemeShim::isActive()) {
    echo '</div>';
}
