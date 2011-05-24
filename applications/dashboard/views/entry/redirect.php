<?php if (!defined('APPLICATION')) exit();
$RedirectUrl = $this->RedirectUrl;

echo '<h1>', T('Redirecting...'), '</h1>',
   '<div><div class="Info">',
   sprintf(T('Please wait while you are redirected. If you are not redirected, click <a href="%s">here</a>.'), Url($RedirectUrl)),
   '<div class="Progress"></div>',
   '</div></div>';
?>