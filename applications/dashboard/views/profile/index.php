<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Vanilla\Theme\BoxThemeShim;

if (BoxThemeShim::isActive()) {
    echo $this->fetchView('before-page-box');
}

echo '<div class="Profile">';
BoxThemeShim::startBox();
include($this->fetchViewLocation('user'));
echo Gdn_Theme::module('ProfileFilterModule');
echo $this->fetchView($this->_TabView, $this->_TabController, $this->_TabApplication);
BoxThemeShim::endBox();
echo '</div>';
