<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();

/**
 * FIXME: [VNLA-1020] This page needs to appear within the new appearance tab
 * at: /appearance/branding
 */
echo \Vanilla\Web\TwigStaticRenderer::renderReactModule('BrandSettingsPage', []);
