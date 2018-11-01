<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @since 2.8
 */

namespace Vanilla\Web\Assets;

use Vanilla\Addon;

/**
 * An webpack asset that it specific to an addon.
 */
class WebpackAddonAsset extends WebpackAsset {
    /**
     * Constructor.
     *
     * @param \Gdn_Request $request The current request.
     * @param DeploymentCacheBuster $cacheBuster A cache buster instance.
     * @param string $extension The file extension to use.
     * @param string $section The section of the site to get scripts for.
     * @see https://docs.vanillaforums.com/developer/tools/building-frontend/#site-sections
     * @param Addon $addon The addon to get an asset for.
     */
    public function __construct(
        \Gdn_Request $request,
        DeploymentCacheBuster $cacheBuster,
        string $extension,
        string $section,
        Addon $addon
    ) {
        parent::__construct($request, $cacheBuster, $extension, $section, $addon->getKey());
        $this->fileSubpath = $section . DS . 'addons';
        $this->webSubpath = $section . '/' . 'addons';
    }
}
