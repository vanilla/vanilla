<?php if (!defined('APPLICATION')) exit(); ?>
<div id="SignIn" class="AjaxForm">
   <?php include($this->FetchViewLocation('SignIn')); ?>
</div>
<div id="Password" class="AjaxForm">
   <?php include($this->FetchViewLocation('PasswordRequest')); ?>
</div>
<div id="Register" class="AjaxForm">
   <?php include($this->FetchViewLocation($this->_RegistrationView())); ?>
</div>