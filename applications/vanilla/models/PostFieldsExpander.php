<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Forum\Models;

use Vanilla\Utility\ArrayUtils;
use Vanilla\Web\AbstractApiExpander;

/**
 * Expands post fields associated with post types. Used primarily on the /post-types endpoints.
 */
class PostFieldsExpander extends AbstractApiExpander
{
    /**
     * @param PostFieldModel $postFieldModel
     */
    public function __construct(private PostFieldModel $postFieldModel)
    {
        $this->addExpandField("postFields", "postTypeID");
    }

    /**
     * @inheritDoc
     */
    public function getFullKey(): string
    {
        return "postFields";
    }

    /**
     * @inheritDoc
     */
    public function resolveFragments(array $recordIDs): array
    {
        $postFields = $this->postFieldModel->getWhere(["postTypeID" => $recordIDs]);
        return ArrayUtils::arrayColumnArrays($postFields, null, "postTypeID");
    }

    /**
     * @inheritDoc
     */
    public function getPermission(): ?string
    {
        return null;
    }
}
