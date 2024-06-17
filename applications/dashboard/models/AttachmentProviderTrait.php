<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

/**
 * Trait AttachmentServiceTrait
 */
trait AttachmentServiceTrait
{
    /**
     * Get the attachment service.
     *
     * @return AttachmentService
     */
    public function attachmentService(): AttachmentService
    {
        return \Gdn::getContainer()->get(AttachmentService::class);
    }
}
