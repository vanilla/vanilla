<?php if (!defined("APPLICATION")) {
    exit();
}

use Vanilla\Web\TwigStaticRenderer;

// FIXME: This is coupled to groups. What about KBs?
echo TwigStaticRenderer::renderReactModule("FollowedContent", [
    "userID" => $this->data("userID"),
]);
