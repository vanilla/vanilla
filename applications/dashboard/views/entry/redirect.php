<?php if (!defined('APPLICATION')) exit();
$RedirectUrl = $this->redirectTo ?: $this->RedirectUrl;

echo '<h1>', t('Redirecting...'), '</h1>',
'<div><div class="P">',
sprintf(t('Please wait while you are redirected. If you are not redirected, click <a href="%s">here</a>.'), htmlspecialchars(url($RedirectUrl))),
'<div class="Progress"></div>',
'</div></div>';
