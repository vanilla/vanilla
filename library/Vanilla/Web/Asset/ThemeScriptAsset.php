<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @since 2.8
 */

namespace Vanilla\Web\Asset;

use Garden\Web\RequestInterface;

/**
 * An asset representing a script containing data for a particular locale.
 */
class ThemeScriptAsset extends SiteAsset {

    /** @var string */
    private $themeKey;

    /**
     * Constructor.
     *
     * @param RequestInterface $request The current request.
     * @param string $themeKey The key of the locale for the asset to represent.
     * @param string $cacheBustingKey A cache busting string..
     */
    public function __construct(RequestInterface $request, string $themeKey, $cacheBustingKey = "") {
        parent::__construct($request, $cacheBustingKey);
        $this->themeKey = $themeKey;
    }

    /**
     * @inheritdoc
     */
    public function getWebPath(): string {

        return $this->makeWebPath(
            '/api/v2/themes',
            $this->themeKey,
            '/assets/javascript.js'
        );
    }

    /**
     * @inheritdoc
     */
    public function isStatic(): bool {
        return true;
    }
}
