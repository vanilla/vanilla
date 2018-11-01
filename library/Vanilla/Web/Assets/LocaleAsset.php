<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @since 2.8
 */

namespace Vanilla\Web\Assets;

/**
 * An asset representing a script containing data for a particular locale.
 */
class LocaleAsset extends AbstractAsset {
    /** @var string */
    private $localeKey;

    /**
     * @inheritdoc
     * @param string $localeKey The key of the locale for the asset to represent.
     */
    public function __construct(\Gdn_Request $request, DeploymentCacheBuster $cacheBuster, string $localeKey) {
        parent::__construct($request, $cacheBuster);
        $this->localeKey = $localeKey;
    }

    /**
     * @inheritdoc
     */
    public function getWebPath(): string {
        // We need a web-root url, not an asset URL because this is an API endpoint resource that is dynamically generated.
        // It cannot have the assetPath joined onto the beginning.
        return AbstractAsset::joinWebPath(
            $this->request->webRoot(),
            '/api/v2/locales',
            $this->localeKey,
            'translations.js'
        );
    }
}
