<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @since 2.8
 */

namespace Vanilla\Web\Assets;

use Garden\Web\RequestInterface;

/**
 * An asset representing a script containing data for a particular locale.
 */
class LocaleAsset extends SiteAsset {
    /** @var string */
    private $localeKey;

    /**
     * Constructor.
     *
     * @param RequestInterface $request The current request.
     * @param DeploymentCacheBuster $cacheBuster A cache buster instance.
     * @param string $localeKey The key of the locale for the asset to represent.
     */
    public function __construct(RequestInterface $request, DeploymentCacheBuster $cacheBuster, string $localeKey) {
        parent::__construct($request, $cacheBuster);
        $this->localeKey = $localeKey;
    }

    /**
     * @inheritdoc
     */
    public function getWebPath(): string {
        // We need a web-root url, not an asset URL because this is an API endpoint resource that is dynamically generated.
        // It cannot have the assetPath joined onto the beginning.
        return SiteAsset::joinWebPath(
            $this->request->webRoot(),
            '/api/v2/locales',
            $this->localeKey,
            'translations.js' . '?h=' . $this->cacheBuster->value()
        );
    }
}
