<?php if (!defined('APPLICATION')) exit(); ?>
<div id="SignIn" class="AjaxForm">
    <?php include($this->fetchViewLocation('SignIn')); ?>
</div>
<div id="Password" class="AjaxForm">
    <?php include($this->fetchViewLocation('PasswordRequest')); ?>
</div>
<div id="Register" class="AjaxForm">
    <?php include($this->fetchViewLocation($this->_RegistrationView())); ?>
</div>
