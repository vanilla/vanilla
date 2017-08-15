<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Profile">
    <?php
    include($this->fetchViewLocation('user'));
    // include($this->fetchViewLocation('tabs'));
    echo Gdn_Theme::module('ProfileFilterModule');
    echo $this->fetchView($this->_TabView, $this->_TabController, $this->_TabApplication);
    ?>
</div>
