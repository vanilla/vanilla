<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent\Embeds;

use Garden\Schema\Schema;
use Vanilla\EmbeddedContent\AbstractEmbed;
use Vanilla\Formatting\Attachment;
use Vanilla\Models\VanillaMediaSchema;

/**
 * File Embed data object.
 */
class FileEmbed extends AbstractEmbed
{
    const TYPE = "file";

    /**
     * @inheritdoc
     */
    protected function getAllowedTypes(): array
    {
        return [self::TYPE];
    }

    /**
     * @inheritdoc
     */
    public function normalizeData(array $data): array
    {
        // The legacy file embeds have everything underneath attributes.
        $attributes = $data["attributes"] ?? null;
        if ($attributes !== null) {
            $data = $attributes + $data;
        }

        if (!isset($data["foreignUrl"])) {
            $data["foreignUrl"] = null;
        }

        // The `type` field may contain the mime-type data.

        return $data;
    }

    /**
     * Render the image out.
     *
     * @return string
     */
    public function renderHtml(): string
    {
        $viewPath = dirname(__FILE__) . "/FileEmbed.twig";
        return $this->renderTwig($viewPath, [
            "url" => $this->getUrl(),
            "name" => $this->data["name"],
            "data" => $this,
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function schema(): Schema
    {
        return new VanillaMediaSchema(true);
    }

    /**
     * Get the embed as an attachment.
     *
     * @return Attachment
     */
    public function asAttachment(): Attachment
    {
        return Attachment::fromArray($this->getData());
    }
}
