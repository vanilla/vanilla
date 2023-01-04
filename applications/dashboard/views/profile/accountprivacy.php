<?php if (!defined("APPLICATION")) {
    exit();
}

use Vanilla\Web\TwigStaticRenderer;

echo TwigStaticRenderer::renderReactModule("AccountSettings", [
    "userID" => $this->data("_EditingUserID"),
]);
?>
