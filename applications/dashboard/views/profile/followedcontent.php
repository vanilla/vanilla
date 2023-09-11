<?php if (!defined("APPLICATION")) {
    exit();
}

use Vanilla\Web\TwigStaticRenderer;

echo TwigStaticRenderer::renderReactModule("FollowedContent", [
    "userID" => $this->data("userID"),
]);