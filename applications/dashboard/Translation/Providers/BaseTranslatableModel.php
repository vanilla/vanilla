<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace MachineTranslation\Providers;

use DiscussionModel;
use Gdn_Model;

/**
 * Base class for translatable model mapping.
 */
class BaseTranslatableModel implements TranslatableModelInterface
{
    /** @var string */
    protected string $contentType = "";

    /** @var string */
    protected string $primaryKey = "";

    /** @var array */
    protected array $contentToTranslate = [];

    /**
     * @inheridoc
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * @inheridoc
     */
    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    /**
     * @inheridoc
     */
    public function getContentToTranslate(int $primaryID = null, array $data = null): array
    {
        if ($primaryID !== null) {
            $data = $this->getContentModel()->getID($primaryID, DATASET_TYPE_ARRAY);
        }
        if (!$data) {
            return [];
        }
        $content = [];
        foreach ($this->contentToTranslate as $key) {
            if (array_key_exists($key, $data)) {
                $content[$key] = $data[$key];
            }
        }
        return $content;
    }

    /**
     * @inheridoc
     */
    public function getContentKeysToTranslate(): array
    {
        return $this->contentToTranslate;
    }

    /**
     * @inheridoc
     */
    public function getContentModel(): Gdn_Model
    {
        return $this->contentModel;
    }

    /**
     * @inheridoc
     */
    public function getObjectKey(array $data): string
    {
        return "";
    }
}
