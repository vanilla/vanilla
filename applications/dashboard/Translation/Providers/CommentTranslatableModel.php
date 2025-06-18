<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace MachineTranslation\Providers;

use CommentModel;

/**
 * Class to provide comment information for translation.
 */
class CommentTranslatableModel extends BaseTranslatableModel
{
    /** @var string */
    protected string $contentType = "comment";

    /** @var string */
    protected string $primaryKey = "commentID";

    /** @var array */
    protected array $contentToTranslate = ["Body", "body"];

    /**
     * CommentLocaleTranslationModel constructor.
     *
     * @param CommentModel $contentModel
     */
    public function __construct(protected CommentModel $contentModel)
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

    /**
     * @inheritdoc
     */
    public function getObjectKey(array $data): string
    {
        if (array_key_exists("commentsByID", $data)) {
            return "commentsByID";
        }
        return "";
    }
}
