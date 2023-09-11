<?php if (!defined("APPLICATION")) {
    exit();
}

echo \Vanilla\Web\TwigStaticRenderer::renderReactModule("EmailSettings", []);
