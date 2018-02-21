<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill\Blots\Embeds;

class VideoBlot extends AbstractBlockEmbedBlot {
    /**
     * @inheritDoc
     */
    protected static function getInsertKey(): string {
        return "insert.video-placeholder";
    }

    /**
     * @inheritDoc
     */
    protected function renderContent(array $data): string {
        $photoUrl = \htmlentities(val("photoUrl", $data, ""), \ENT_QUOTES);
        $url = \htmlentities(val("url", $data, ""), \ENT_QUOTES);
        $name = val("name", $data, "");

        $playIcon = "<svg class=\"embedVideo-playIcon\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"-1 -1 24 24\"><title>Play Video</title><path class=\"embedVideo-playIconPath embedVideo-playIconPath-circle\" style=\"fill: currentColor; stroke-width: .3;\" d=\"M11,0A11,11,0,1,0,22,11,11,11,0,0,0,11,0Zm0,20.308A9.308,9.308,0,1,1,20.308,11,9.308,9.308,0,0,1,11,20.308Z\"></path><polygon class=\"embedVideo-playIconPath embedVideo-playIconPath-triangle\" style=\"fill: currentColor; stroke-width: .3;\" points=\"8.609 6.696 8.609 15.304 16.261 11 8.609 6.696\"></polygon></svg>";

        // TODO: Calculate the ratio in the scrape endpoint and use it here.
        $defaultRatioParams = 'style="padding-top: 68.67599569429494%;"';

        $result =   "<div class=\"embed embedVideo\">";
        $result .=      "<div class=\"embedVideo-ratio\" $defaultRatioParams>";
        $result .=          "<button type=\"button\" data-url=\"$url\" aria-label=\"$name\" ";
        $result .=              "class=\"embedVideo-playButton iconButton js-playVideo\" ";
        $result .=              "style=\"background-image: url($photoUrl);\">";
        $result .=               $playIcon;
        $result .=          "</button>";
        $result .=      "</div>";
        $result .=  "</div>";

        return $result;
    }
}
