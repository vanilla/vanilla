<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2024 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Models;

use Gdn;
use Vanilla\Logging\ErrorLogger;
use Vanilla\Models\SiteMetaExtra;

/**
 * Extra site meta for attachments.
 */
class AttachmentMeta extends SiteMetaExtra
{
    protected AttachmentService $attachmentService;

    /**
     * @param AttachmentService $attachmentService
     */
    public function __construct(AttachmentService $attachmentService)
    {
        $this->attachmentService = $attachmentService;
    }

    /**
     * @return array
     */
    public function getValue(): array
    {
        return [
            "externalAttachments" => $this->attachmentService->getCatalog(),
        ];
    }
}
