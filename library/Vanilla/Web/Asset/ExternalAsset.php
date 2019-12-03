<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web\Asset;

use Vanilla\Contracts\Web\AssetInterface;

/**
 * Class representing an external asset of some type.
 */
class ExternalAsset implements AssetInterface {

    /** @var string */
    private $url;

    /**
     * @param string $url
     */
    public function __construct(string $url) {
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getWebPath(): string {
        return $this->url;
    }

    /**
     * @inheritdoc
     */
    public function isStatic(): bool
    {
        return false;
    }
}
