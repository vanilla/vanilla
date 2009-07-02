<?php if (!defined('APPLICATION')) exit();

// Make sure to force this form to post to the correct place in case the view is
// rendered within another view (ie. /garden/entry/index/):
?>
<h1><?php echo Translate("Reset my password") ?></h1>
<p><?php echo Translate('An message has been sent to your email address with password reset instructions.'); ?></p>