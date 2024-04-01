<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2024 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Models;

use Gdn;
use Vanilla\Models\SiteMetaExtra;

/**
 * Extra site meta for attachments.
 */
class AttachmentMeta extends SiteMetaExtra
{
    protected ExternalIssueService $externalIssueService;

    /**
     * @param ExternalIssueService $externalIssueService
     */
    public function __construct(ExternalIssueService $externalIssueService)
    {
        $this->externalIssueService = $externalIssueService;
    }

    /**
     * @return array
     */
    public function getValue(): array
    {
        $providers = $this->externalIssueService->getAllProviders();
        $catalog = [];
        foreach ($providers as $provider) {
            if (!$provider->validatePermissions(Gdn::session()->User)) {
                continue;
            }
            $catalog[$provider->getTypeName()] = $provider->getCatalog();
        }

        return ["externalAttachments" => $catalog];
    }
}
