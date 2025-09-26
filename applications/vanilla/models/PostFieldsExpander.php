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
     * @inheritdoc
     */
    public function getFullKey(): string
    {
        return "postFields";
    }

    /**
     * @inheritdoc
     */
    public function resolveFragments(array $recordIDs): array
    {
        $postFields = $this->postFieldModel->getPostFieldsByPostTypes(["ptpf.postTypeID" => $recordIDs]);
        $postFields = ArrayUtils::arrayColumnArrays($postFields, null, "postTypeID");
        foreach ($postFields as &$group) {
            // Make sure post fields are sorted in each group.
            usort($group, fn($field1, $field2) => $field1["sort"] <=> $field2["sort"]);
        }
        return $postFields;
    }

    /**
     * @inheritdoc
     */
    public function getPermission(): ?string
    {
        return null;
    }
}
