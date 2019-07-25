<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

 namespace Vanilla\Theme;

use JsonSerializable;
 
/**
 * A representation of a font.
*/
class Font implements JsonSerializable {

    /** @var array A list of fallback font names. */
    private $fallbacks = [];

    /** @var array Name of the font. */
    private $name;

    /** @var string Type of asset. */
    private $type = "url";

    /** @var string Absolute URL to the font file. */
    private $url;

    /**
     * Configure the font asset.
     *
     * @param string $name
     * @param string $url
     * @param array $fallbacks
     */
    public function __construct(string $name, string $url, array $fallbacks = []) {
        $this->name = $name;
        $this->url = $url;
        foreach ($fallbacks as $fallback) {
            $this->addFallback($fallback);
        }
    }

    /**
     * Represent the font as an array.
     *
     * @return array
     */
    public function asArray(): array {
        return [
            "fallbacks" => $this->fallbacks,
            "name" => $this->name,
            "type" => $this->type,
            "url" => $this->url,
        ];
    }

    /**
     * Add a new fallback for this font.
     *
     * @param string $fallback
     */
    private function addFallback(string $fallback) {
        $this->fallbacks[] = trim($fallback);
    }

    /**
     * Specify data which should be serialized to JSON.
     */
    public function jsonSerialize(): array {
        $array = $this->asArray();
        $array["fallbacks"] = $array["fallbacks"] ? implode(",", $array["fallbacks"]) : null;
        return $array;
    }
}
