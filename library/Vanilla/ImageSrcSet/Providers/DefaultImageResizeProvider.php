<?php
/**
 * @author David Barbier <dbarbier@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\ImageSrcSet\Providers;

use Vanilla\ImageSrcSet\ImageResizeProviderInterface;

/**
 * Provides a single image url based on its initial URL & desired maximum width.
 * Note: The default provider systematically return an empty string.
 */
class DefaultImageResizeProvider implements ImageResizeProviderInterface
{
    /**
     * Returns a resized image URL.
     *
     * @param string|null $initialUrl
     * @param int $maxWidth
     * @return string|null
     */
    public function getResizedImageUrl(?string $initialUrl, int $maxWidth): ?string
    {
        return "";
    }
}
