<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Embeds;

/**
 * Base video embed class. Includes utility functions specifically for video content.
 */
abstract class VideoEmbed extends Embed {

    /**
     * Calculate an aspect ratio (e.g. 16:9) from the width and height.
     *
     * @param int $width
     * @param int $height
     * @return string
     */
    protected function calculateRatio(int $width, int $height): string {
        $findGCD = function($a, $b) use (&$findGCD) {
            return $b ? $findGCD($b, $a % $b) : $a;
        };
        $gcd = $findGCD($width, $height);

        $width = $width / $gcd;
        $height = $height / $gcd;

        $result = "{$width}:{$height}";
        return $result;
    }

    /**
     * Calculate attributes for the primary video container.
     *
     * @param int $width
     * @param int $height
     * @return array
     */
    protected function videoContainerAttributes(int $width, int $height): array {
        $result = [
            'class' => 'embedVideo-ratio',
            'style' => ''
        ];

        if ($height && $width) {
            $ratio = $this->calculateRatio($width, $height);
            switch ($ratio) {
                case '21:9':
                case '16:9':
                case '4:3':
                case '1:1':
                    $result['class'] .= ' is'.str_replace(':', 'by', $ratio);
                    break;
                default:
                    $percentage = ($height/$width) * 100;
                    $result['style'] = "padding-top: {$percentage}%;";
            }
        }

        return $result;
    }

    /**
     * Generate the code necessary for an embedded video.
     *
     * @param string $embedUrl
     * @param string $name
     * @param string $photoUrl
     * @param int $width
     * @param int $height
     * @return string
     */
    protected function videoCode(string $embedUrl, string $name, string $photoUrl, int $width, int $height): string {
        $attr = [
            'url' => htmlspecialchars(\Gdn_Format::sanitizeUrl($embedUrl)),
            'name' => htmlspecialchars($name),
            'photoUrl' => htmlspecialchars($photoUrl)
        ];

        $containerAttr = $this->videoContainerAttributes($width, $height);

        $playButtonSVG = <<<SVG
<svg class="embedVideo-playIcon" xmlns="http://www.w3.org/2000/svg" viewBox="-1 -1 24 24">
    <title>Play Video</title>
    <path class="embedVideo-playIconPath embedVideo-playIconPath-circle" style="fill: currentColor; stroke-width: .3;" d="M11,0A11,11,0,1,0,22,11,11,11,0,0,0,11,0Zm0,20.308A9.308,9.308,0,1,1,20.308,11,9.308,9.308,0,0,1,11,20.308Z"></path>
    <polygon class="embedVideo-playIconPath embedVideo-playIconPath-triangle" style="fill: currentColor; stroke-width: .3;" points="8.609 6.696 8.609 15.304 16.261 11 8.609 6.696"></polygon>
</svg>
SVG;

        $imgAlt = t("A thumnail preview of a video");

        $result = <<<HTML
<div class="embedExternal embedVideo">
    <div class="embedExternal-content">
        <div class="{$containerAttr['class']}" style="{$containerAttr['style']}">
            <button type="button" data-url="{$attr['url']}" aria-label="{$attr['name']}" class="embedVideo-playButton js-playVideo" title="{$attr['name']}">
                <img class="embedVideo-thumbnail" src="{$attr['photoUrl']}" role="presentation" alt="{$imgAlt}"/>
                <span class="videoEmbed-scrim"/>
                {$playButtonSVG}
            </button>
        </div>
    </div>
</div>
HTML;
        return $result;
    }
}
