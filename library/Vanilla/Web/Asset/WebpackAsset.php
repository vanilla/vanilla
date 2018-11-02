<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @since 2.8
 */

namespace Vanilla\Web\Asset;

use Garden\Web\RequestInterface;
use Vanilla\Contracts;

/**
 * An asset representing a file created by the webpack build process.
 */
class WebpackAsset extends SiteAsset {
    const SCRIPT_EXTENSION = ".min.js";
    const STYLE_EXTENSION = ".min.css";

    /** @var string */
    protected $fsRoot = PATH_ROOT;

    /** @var string */
    protected $assetName;

    /** @var string */
    protected $extension;

    /** @var string The subpath between the script name and the DIST root on the filesystem. */
    protected $fileSubpath;

    /** @var string The subpath between the script name and the DIST root on the web server.  */
    protected $webSubpath;

    /**
     * Constructor.
     *
     * @param RequestInterface $request The current request.
     * @param Contracts\Web\CacheBusterInterface $cacheBuster A cache buster instance.
     * @param string $extension The file extension to use.
     * @param string $section The section of the site to get scripts for.
     * @see https://docs.vanillaforums.com/developer/tools/building-frontend/#site-sections
     * @param string $assetName The name of the asset to get.
     */
    public function __construct(
        RequestInterface $request,
        Contracts\Web\CacheBusterInterface $cacheBuster,
        string $extension,
        string $section,
        string $assetName
    ) {
        parent::__construct($request, $cacheBuster);
        $this->extension = $extension;
        $this->assetName = $assetName;
        $this->fileSubpath = $section;
        $this->webSubpath = $section;
    }

    /**
     * @inheritdoc
     */
    public function getWebPath(): string {
        return $this->makeAssetPath(
            'dist',
            $this->webSubpath,
            $this->assetName . $this->extension
        );
    }

    public function existsOnFs(): bool {
        return file_exists($this->getFilePath());
    }

    /**
     * Get the file path of the asset.
     */
    private function getFilePath(): string {
        return SiteAsset::joinFilePath(
            $this->fsRoot,
            "dist",
            $this->fileSubpath,
            $this->assetName . $this->extension
        );
    }

    /**
     * @param string $assetRoot
     */
    public function setFsRoot(string $assetRoot) {
        $this->fsRoot = $assetRoot;
    }
}
