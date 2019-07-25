<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

 namespace Vanilla\Theme;

use JsonSerializable;
 
/**
 * A representation of a JavaScript file.
*/
class Script implements JsonSerializable {

    /** @var string Absolute URL to the script file. */
    private $url;

    /**
     * Configure the script asset.
     *
     * @param string $url
     */
    public function __construct(string $url) {
        $this->url = $url;
    }

    /**
     * Represent the script as an array.
     *
     * @return array
     */
    public function asArray(): array {
        return [
            "url" => $this->url,
        ];
    }

    /**
     * Specify data which should be serialized to JSON.
     */
    public function jsonSerialize(): array {
        return $this->asArray();
    }
}
