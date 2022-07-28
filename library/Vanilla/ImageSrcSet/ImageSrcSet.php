<?php
/**
 * @author David Barbier <dbarbier@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\ImageSrcSet;

/**
 * A serializable image srcset.
 */
class ImageSrcSet implements \JsonSerializable
{
    /** @var array */
    private $data;

    /**
     * Add an image url to the serializable data array.
     *
     * @param int $size
     * @param string $url
     * @return void
     */
    public function addUrl(int $size, string $url): void
    {
        $this->data[$size] = $url;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        return $this->data;
    }
}
