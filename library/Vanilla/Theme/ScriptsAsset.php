<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

 namespace Vanilla\Theme;

 /**
  * A collection of JavaScript files for a theme.
  */
class ScriptsAsset extends Asset {

    /** @var Script[] List of JavaScript files. */
    private $data = [];

    /** @var string Type of asset. */
    protected $type = "data";

    /**
     * Configure the scripts collection asset.
     *
     * @param string $data
     */
    public function __construct(array $data = []) {
        foreach ($data as $scriptConfig) {
            $script = new Script($scriptConfig["url"]);
            $this->addScript($script);
        }
    }

    /**
     * Represent the script collection asset as an array.
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
     * Get the collection of scripts.
     *
     * @return string
     */
    public function getData(): array {
        return $this->data;
    }

    /**
     * Add a new script.
     *
     * @param Script $script
     */
    private function addScript(Script $script) {
        $this->data[] = $script;
    }
}
