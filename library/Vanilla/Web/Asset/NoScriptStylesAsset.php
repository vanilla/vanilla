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
class NoScriptStylesAsset extends SiteAsset
{
    // Used in PageHead.twig
    public bool $isNoScript = true;

    /**
     * Constructor.
     *
     * @param RequestInterface $request The current request.
     * @param DeploymentCacheBuster $cacheBuster
     */
    public function __construct(RequestInterface $request, DeploymentCacheBuster $cacheBuster)
    {
        parent::__construct($request, $cacheBuster->value());
    }

    /**
     * @inheritdoc
     */
    public function getWebPath(): string
    {
        return $this->makeAssetPath("/resources/design/no-script-layout-styles.css");
    }
}
