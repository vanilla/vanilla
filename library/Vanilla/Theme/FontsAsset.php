<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

 namespace Vanilla\Theme;

 /**
  * A collection of fonts for a theme.
  */
class FontsAsset extends Asset {

    /** @var Font[] List of fonts. */
    private $data = [];

    /** @var string Type of asset. */
    protected $type = "data";

    /**
     * Configure the font collection asset.
     *
     * @param string $data
     */
    public function __construct(array $data = []) {
        foreach ($data as $fontConfig) {
            $font = new Font(
                $fontConfig["name"],
                $fontConfig["url"],
                $fontConfig["fallbacks"] ? explode(",", $fontConfig["fallbacks"]) : []
            );
            $this->addFont($font);
        }
    }

    /**
     * Represent the font collection asset as an array.
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
     * Get the collection of fonts.
     *
     * @return string
     */
    public function getData(): array {
        return $this->data;
    }

    /**
     * Add a new font.
     *
     * @param Font $font
     */
    private function addFont(Font $font) {
        $this->data[] = $font;
    }
}
