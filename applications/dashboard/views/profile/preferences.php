<?php if (!defined("APPLICATION")) {
    exit();
}

use Vanilla\Web\TwigStaticRenderer;

echo TwigStaticRenderer::renderReactModule("NotificationPreferences", [
    "userID" => $this->data("userID"),
]);
