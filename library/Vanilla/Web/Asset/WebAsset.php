<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web\Asset;

use Vanilla\Contracts\Web\AssetInterface;
use Vanilla\Contracts\Web\AssetTrait;

/**
 * Class representing an web asset of some type.
 */
class WebAsset implements AssetInterface
{
    use AssetTrait;

    private string $url;

    private bool $isScriptModule;

    /**
     * @param string $url
     * @param bool $isScriptModule
     */
    public function __construct(string $url, bool $isScriptModule = false)
    {
        $this->url = $url;
        $this->isScriptModule = $isScriptModule;
    }

    /**
     * @return string
     */
    public function isScriptModule(): string
    {
        return $this->isScriptModule;
    }

    /**
     * @return string
     */
    public function getWebPath(): string
    {
        return $this->url;
    }
}
