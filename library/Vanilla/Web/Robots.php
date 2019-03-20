<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPLv2.0-only
 */

namespace Vanilla\Web;

/**
 * A data collector for robots.txt data.
 */
class Robots implements \JsonSerializable {
    /**
     * @var array
     */
    private $sitemaps = [];

    /**
     * @var array
     */
    private $rules = [];

    /**
     * Add a site map to the robots file.
     * @param string $url The URL to add. This will be wrapped in `url()` when rendered.
     */
    public function addSitemap(string $url) {
        $this->sitemaps[] = $url;
    }

    /**
     * Add a rule to the robots file.
     *
     * @param string $rule The rule to add.
     */
    public function addRule(string $rule) {
        $this->rules[] = trim($rule);
    }

    /**
     * Specify data which should be serialized to JSON
     *
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize() {
        return ['sitemaps' => $this->sitemaps, 'rules' => $this->rules];
    }

    /**
     * Get the list of site maps.
     *
     * @return array Returns an array of URLs.
     */
    public function getSitemaps(): array {
        return $this->sitemaps;
    }

    /**
     * Get the list of robots rules.
     *
     * @return array Returns an array of rules.
     */
    public function getRules(): array {
        return $this->rules;
    }
}
