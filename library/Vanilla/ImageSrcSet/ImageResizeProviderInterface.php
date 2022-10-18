<?php
/**
 * @author David Barbier <dbarbier@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\ImageSrcSet;

/**
 * Implementable interface to provide a resized image url.
 */
interface ImageResizeProviderInterface
{
    /**
     * Returns a resized image URL.
     *
     * @param string|null $initialUrl
     * @param int $maxWidth
     * @return string|null
     */
    public function getResizedImageUrl(?string $initialUrl, int $maxWidth): ?string;
}
