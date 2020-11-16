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

    const DOMAIN = "https://webpack.vanilla.localhost:3030";

    /** @var string */
    private $section;

    /**
     * HotBuildAsset constructor.
     *
     * @param string $section The section of the site to serve. `forum`, `admin`, etc.
     * @see {https://docs.vanillaforums.com/developer/tools/building-frontend/#site-sections}
     */
    public function __construct(string $section) {
        $this->section = $section;
    }

    /**
     * @inheritdoc
     */
    public function getWebPath(): string {
        return self::DOMAIN . "/{$this->section}-hot-bundle.js";
    }

    /**
     * @inheritdoc
     */
    public function isStatic(): bool {
        return false;
    }
}
