<?php
/**
 * @author David Barbier <dbarbier@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures;

use Vanilla\ImageSrcSet\ImageResizeProviderInterface;

/**
 * For testing purpose.
 * Provides a single image url based on its initial URL & desired maximum width.
 */
class MockImageSrcSetProvider implements ImageResizeProviderInterface
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
        $filename = pathinfo($initialUrl, PATHINFO_FILENAME);
        return "https://loremflickr.com/g/" . $maxWidth . "/600/" . $filename;
    }
}
