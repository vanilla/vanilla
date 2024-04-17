<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

use Vanilla\Web\TwigStaticRenderer;

if (!function_exists("WriteAttachments")) {
    /**
     * Render attachment HTML. This method is left around due to some custom view overrides calling it.
     *
     * @param array $attachments
     */
    function writeAttachments(array $attachments)
    {
        \Gdn::controller()->fireEvent("beforeWriteAttachments");
        if (!empty($attachments)) {
            echo TwigStaticRenderer::renderReactModule("LegacyThreadAttachmentsAsset", ["attachments" => $attachments]);
        }
    }
}
