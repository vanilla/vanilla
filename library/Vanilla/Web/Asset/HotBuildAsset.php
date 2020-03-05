<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @since 2.8
 */

namespace Vanilla\Web\Asset;

use Vanilla\Contracts;

/**
 * Class representing the asset used by hot webpack build. Eg.
 * `yarn build:dev`.It points to the development bundle on the webpack dev server.
 */
class HotBuildAsset implements Contracts\Web\AssetInterface {
    /** @var string */
    private $ip;

    /** @var string */
    private $section;

    /**
     * HotBuildAsset constructor.
     *
     * @param string $section The section of the site to serve. `forum`, `admin`, etc.
     * @see {https://docs.vanillaforums.com/developer/tools/building-frontend/#site-sections}
     * @param string|null $ip The IP address to serve the build asset from.
     */
    public function __construct(string $section, $ip = null) {
        if ($ip === null) {
            $ip = "127.0.0.1";
        }
        $this->ip = $ip;
        $this->section = $section;
    }

    /**
     * @inheritdoc
     */
    public function getWebPath(): string {
        return "http://$this->ip:3030/$this->section-hot-bundle.js";
    }

    /**
     * @inheritdoc
     */
    public function isStatic(): bool {
        return false;
    }
}
