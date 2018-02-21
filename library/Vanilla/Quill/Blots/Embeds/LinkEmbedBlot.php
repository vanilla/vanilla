<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill\Blots\Embeds;

class LinkEmbedBlot extends AbstractBlockEmbedBlot {
    /**
     * @inheritDoc
     */
    protected static function getInsertKey(): string {
        return "insert.link-embed";
    }

    /**
     * @inheritDoc
     */
    protected function renderContent(array $data): string {
        $url = \htmlentities(val("url", $data, ""), \ENT_QUOTES);
        $imageUrl = \htmlentities(val("linkImage", $data, ""), \ENT_QUOTES);
        $excerpt = val("excerpt", $data, "");

        $excerptHtml = "<div class=\"embedLink-excerpt\">$excerpt</div>";
        $headerHtml = $this->renderHeader($data);

        $main = '<div class="embedLink-main">' . $headerHtml . $excerptHtml . '</div>';


        $result = "<a class=\"embed embedLink\" href=\"$url\" target=\"_blank\" rel=\"noopener noreferrer\">";
        $result .= '<article class="embedLink-body">';

        if ($imageUrl) {
            $result .= '<div class="embedLink-image" aria-hidden="true" ';
            $result .=      'style="background-image: url(' . $imageUrl . ');"></div>';
        }

        $result .= $main;
        $result .= '</article>';
        $result .= "</a>";

        return $result;
    }

    /**
     * Render the header part of the embed.
     *
     * @param array $data - The data from the blot.
     *
     * @return string
     */
    private function renderHeader(array $data): string {
        $userPhoto = val("userPhoto", $data);
        $userName = val("userName", $data);
        $timestamp = val("timestamp", $data);
        $humanTime = val("humanTime", $data);
        $name = val("name", $data);
        $source = val("source", $data);

        $result = "<div class=\"embedLink-header\">";

        if ($userPhoto) {
            $result .= "<span class=\"embedLink-userPhoto PhotoWrap\">";
            $result .=      "<img src=\"$userPhoto\" alt=\"$userName\" class=\"ProfilePhoto ProfilePhotoMedium\">";
            $result .= "</span>";
        }

        if ($userName) {
            $result .= "<span class=\"embedLink-userName\">$userName</span>";
        }

        if ($timestamp) {
            $result .= "<time class=\"embedLink-dateTime meta\" datetime=\"$timestamp\">$humanTime</time>";
        }

        if ($name) {
            $result .= "<h3 class=\"embedLink-title\">$name</h3>";
        }

        if ($source) {
            $result .= "<span class=\"embedLink-source meta\">$source</span>";
        }

        $result .= "</div>";
        return $result;
    }

    private function sanitizeUrl($url) {
        // TODO: Sanitize URLs
    }
}
