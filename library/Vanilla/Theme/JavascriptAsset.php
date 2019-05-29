<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Theme;

/**
 * Style theme asset.
 */
class JavascriptAsset extends Asset {

    /** @var string Javascript content of this asset. */
    private $data;

    /** @var string Type of asset. */
    protected $type = "js";

    /**
     * Configure the JSON asset.
     *
     * @param string $data
     */
    public function __construct($data) {
        $this->data = $data;
    }

    /**
     * Represent the Javascript asset as an array.
     *
     * @return array
     */
    public function asArray(): array {
        return [
            "data" => $this->data,
            "type" => $this->type,
        ];
    }

    /**
     * Get javascript code.
     *
     * @return string
     */
    public function getData(): string {
        return $this->data;
    }
}
