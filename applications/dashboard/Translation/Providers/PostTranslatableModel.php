<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace MachineTranslation\Providers;

use DiscussionModel;

/**
 * Class to provide post information for translation.
 */
class PostTranslatableModel extends BaseTranslatableModel
{
    /** @var string */
    protected string $contentType = "discussion";

    /** @var string */
    protected string $primaryKey = "discussionID";

    /** @var array */
    protected array $contentToTranslate = ["name", "Name", "body", "Body"];

    /**
     * GPT Translation constructor.
     *
     * @param DiscussionModel $contentModel
     */
    public function __construct(protected DiscussionModel $contentModel)
    {
    }

    /**
     * @inheritdoc
     */
    public function getContentToTranslate(int $primaryID = null, array $data = null): array
    {
        if ($primaryID !== null) {
            $data = $this->getContentModel()->getID($primaryID, DATASET_TYPE_ARRAY);
            if (!$primaryID || !$data) {
                return [];
            }
            $data = $this->getContentModel()->normalizeRow($data);
        }
        return parent::getContentToTranslate(null, $data);
    }
}
