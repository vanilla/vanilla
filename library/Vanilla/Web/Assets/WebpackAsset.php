<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @since 2.8
 */

namespace Vanilla\Web\Assets;

/**
 * An asset representing a file created by the webpack build process.
 */
class WebpackAsset extends AbstractAsset {
    const SCRIPT_EXTENSION = ".min.js";
    const STYLE_EXTENSION = ".min.css";

    /** @var string */
    protected $assetName;

    /** @var string */
    protected $extension;

    /** @var string The subpath between the script name and the DIST root on the filesystem. */
    protected $fileSubpath;

    /** @var string The subpath between the script name and the DIST root on the web server.  */
    protected $webSubpath;

    /**
     * @inheritdoc
     * @see https://docs.vanillaforums.com/developer/tools/building-frontend/#site-sections
     * @param string $extension The file extension to use.
     * @param string $section The section of the site to get scripts for.
     * @param string $assetName The name of the asset to get.
     */
    public function __construct(
        \Gdn_Request $request,
        CacheBusterInterface $cacheBuster,
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
            $this->assetName . $this->extension . '?h=' . $this->cacheBuster->value()
        );
    }

    /**
     * Get the file path of the asset.
     */
    public function getFilePath(): string {
        return AbstractAsset::joinFilePath(
            PATH_ROOT,
            "dist",
            $this->fileSubpath,
            $this->assetName . $this->extension
        );
    }
}
