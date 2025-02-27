<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\EmbeddedContent\Embeds;

use Vanilla\EmbeddedContent\AbstractEmbed;
use Vanilla\EmbeddedContent\EmbedFilterInterface;

class FileEmbedFilter implements EmbedFilterInterface
{
    /**
     * @inheritDoc
     */
    public function canHandleEmbedType(string $embedType): bool
    {
        return $embedType === FileEmbed::TYPE;
    }

    /**
     * @inheritDoc
     */
    public function filterEmbed(AbstractEmbed $embed): AbstractEmbed
    {
        if ($embed instanceof FileEmbed) {
            $data = $embed->getData();
            if (isset($data["mediaID"])) {
                $embed->updateData([
                    "url" => \Gdn::request()->getSimpleUrl("/api/v2/media/{$data["mediaID"]}/download"),
                ]);
            }
        }
        return $embed;
    }
}
