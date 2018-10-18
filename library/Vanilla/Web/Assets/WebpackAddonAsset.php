<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @since 2.8
 */

namespace Vanilla\Web\Assets;

use Vanilla\Addon;

class WebpackAddonAsset extends WebpackAsset {
    public function __construct(
        \Gdn_Request $request,
        CacheBusterInterface $cacheBuster,
        string $extension,
        string $section,
        Addon $addon
    ) {
        parent::__construct($request, $cacheBuster, $extension, $section, $addon->getKey());
        $this->fileSubpath = $section . DS . 'addons';
        $this->webSubpath = $section . '/' . 'addons';
    }
}
