<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla\Embeds;


abstract class AbstractEmbed {
    protected $domains;

    /**
     *
     * @param string $url
     * @return array|null
     */
    abstract function matchUrl(string $url);

    /**
     * @param array $data
     * @return mixed
     */
    abstract function renderContent(array $data);

    // abstract function getScripts(array $data);

    /**
     * Get the domains.
     *
     * @return mixed Returns the domains.
     */
    public function getDomains(): mixed {
        return $this->domains;
    }

    abstract function getType(): string;

    abstract function getRenderType(): string;
}
