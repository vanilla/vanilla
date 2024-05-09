<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @since 2.8
 */

namespace Vanilla\Web\Asset;

use Garden\Web\RequestInterface;

/**
 * An asset representing a file created by the webpack build process.
 */
class WebpackAsset extends SiteAsset
{
    const SCRIPT_EXTENSION = ".min.js";
    const STYLE_EXTENSION = ".min.css";

    /** @var string */
    protected $fsRoot = PATH_ROOT;

    /** @var string */
    protected $assetPath;

    /**
     * Constructor.
     *
     * @param RequestInterface $request The current request.
     * @param string $assetPath
     */
    public function __construct(RequestInterface $request, string $assetPath)
    {
        parent::__construct($request);
        $this->assetPath = $assetPath;
    }

    /**
     * @inheritdoc
     */
    public function getWebPath(): string
    {
        return $this->makeAssetPath($this->assetPath);
    }

    /**
     * Check if the asset exists on the file system.
     *
     * @return bool
     */
    public function existsOnFs(): bool
    {
        return file_exists($this->getFilePath());
    }

    /**
     * Get the file path of the asset.
     */
    private function getFilePath(): string
    {
        return SiteAsset::joinFilePath($this->fsRoot, $this->assetPath);
    }

    /**
     * @param string $assetRoot
     */
    public function setFsRoot(string $assetRoot)
    {
        $this->fsRoot = $assetRoot;
    }

    /**
     * @inheritdoc
     */
    public function isStatic(): bool
    {
        return true;
    }
}
