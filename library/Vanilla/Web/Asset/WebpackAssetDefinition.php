<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web\Asset;

use Garden\Web\RequestInterface;

/**
 * Store a webpack asset definition.
 */
final class WebpackAssetDefinition
{
    /** @var string */
    private $assetPath;

    /** @var string */
    private $assetType;

    /** @var string */
    private $section;

    /** @var string|null */
    private $addonKey;

    /**
     * Constructor.
     *
     * @param string $assetPath
     * @param string $assetType
     * @param string $section
     * @param string|null $addonKey
     */
    public function __construct(string $assetPath, string $assetType, string $section, ?string $addonKey)
    {
        $this->assetPath = $assetPath;
        $this->assetType = $assetType;
        $this->section = $section;
        $this->addonKey = $addonKey;
    }

    /**
     * Support {@link var_export()} for caching.
     *
     * @param array $array The array to load.
     * @return WebpackAssetDefinition Returns a new definition with the properties from {@link $array}.
     */
    public static function __set_state(array $array): WebpackAssetDefinition
    {
        $array += ["assetPath" => "", "assetType" => [], "section" => [], "addonKey" => null];
        return new WebpackAssetDefinition(
            $array["assetPath"],
            $array["assetType"],
            $array["section"],
            $array["addonKey"]
        );
    }

    /**
     * Convert the definition into an actual site asset.
     *
     * @param RequestInterface $request
     *
     * @return WebpackAsset
     */
    public function asAsset(RequestInterface $request): WebpackAsset
    {
        return new WebpackAsset($request, $this->assetPath);
    }

    /**
     * @return string
     */
    public function getAssetPath(): string
    {
        return $this->assetPath;
    }

    /**
     * @return string
     */
    public function getAssetType(): string
    {
        return $this->assetType;
    }

    /**
     * @return string
     */
    public function getSection(): string
    {
        return $this->section;
    }

    /**
     * @return string|null
     */
    public function getAddonKey(): ?string
    {
        return $this->addonKey;
    }
}
