<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @since 2.8
 */

namespace Vanilla\Web\Asset;

use Garden\Web\RequestInterface;
use Vanilla\Contracts;

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
     * @param string $localeKey The key of the locale for the asset to represent.
     * @param string $cacheBustingKey A cache busting string..
     */
    public function __construct(RequestInterface $request, string $localeKey, $cacheBustingKey = "") {
        parent::__construct($request, $cacheBustingKey);
        $this->localeKey = $localeKey;
    }

    /**
     * @inheritdoc
     */
    public function getWebPath(): string {
        return self::makeAssetPath(
            '/api/v2/locales',
            $this->localeKey,
            'translations.js'
        );
    }
}
