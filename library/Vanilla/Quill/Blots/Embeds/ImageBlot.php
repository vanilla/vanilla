<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill\Blots\Embeds;

class ImageBlot extends AbstractBlockEmbedBlot {
    /**
     * @inheritDoc
     */
    protected static function getInsertKey(): string {
        return "insert.embed-image";
    }

    /**
     * @inheritDoc
     */
    protected function renderContent(array $data): string {
        $imageUrl = \htmlentities(val("url", $data, ""), \ENT_QUOTES);
        $altText = val("alt", $data, "");

        $result =   "<div class=\"embed-image embed embedImage\">";
        $result .=      "<img class=\"embedImage-img\" src=\"$imageUrl\" alt=\"$altText\">";
        $result .=  "</div>";

        return $result;
    }
}
