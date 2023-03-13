<?php if (!defined('APPLICATION')) exit();

use Vanilla\Web\TwigStaticRenderer;

echo TwigStaticRenderer::renderReactModule("EditProfileFields", ["userID" => $this->data("userID")]);