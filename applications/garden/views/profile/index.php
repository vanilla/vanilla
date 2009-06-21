<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Profile">
   <?php
   include($this->FetchViewLocation('user'));
   include($this->FetchViewLocation('tabs'));
   include($this->FetchViewLocation($this->_TabView, $this->_TabController, $this->_TabApplication));
   ?>
</div>