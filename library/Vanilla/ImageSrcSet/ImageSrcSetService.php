<?php
/**
 * @author David Barbier <dbarbier@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\ImageSrcSet;

/**
 * Service that provides Image's srcset.
 */
class ImageSrcSetService
{
    /** @var ImageResizeProviderInterface|null */
    private $provider;

    /**
     * @param ImageResizeProviderInterface $provider
     */
    public function setImageResizeProvider(ImageResizeProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Returns an ImageSrcSet(or null) element containing images URLs & sizes.
     *
     * @param string|null $initialUrl
     * @return ImageSrcSet|null
     */
    public function getResizedSrcSet(?string $initialUrl): ?ImageSrcSet
    {
        $requiredSizes = [10, 300, 800, 1200, 1600];

        if (!$this->provider) {
            return null;
        }

        if ($initialUrl === null) {
            return null;
        }

        $imageSrcSet = new ImageSrcSet();

        foreach ($requiredSizes as $requiredSize) {
            $url = $this->provider->getResizedImageUrl($initialUrl, $requiredSize);
            if (!is_null($url)) {
                $imageSrcSet->addUrl($requiredSize, $url);
            }
        }

        return $imageSrcSet;
    }
}
