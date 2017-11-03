<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla;

/**
 * Offers utility methods for resizing image files.
 */
class ImageResizer {

    /**
     * Calculate an image crop based on source image information and options.
     *
     * @param array $source Information about the source image. It should have the following keys:
     *
     * - width: The width of the image.
     * - height: The height of the image.
     * @param array $options An array of options constraining the image crop.
     *
     * - width: The width of the destination image.
     * - height: The height of the destination image.
     * @return array Returns an array of resizing information.
     *
     * - width: The width of the destination image.
     * - height: The height of the destination image.
     * - sourceX: The starting horizontal location of the source image.
     * - sourceY: The starting vertical position of the source image.
     * - sourceWidth: The sample width of the source image.
     * - sourceHeight: The sample height of the source image.
     */
    public function calculateResize(array $source, array $options) {
        if (empty($source['height'])) {
            throw new \InvalidArgumentException('Missing argument $source["height"].', 400);
        } elseif (empty($source['width'])) {
            throw new \InvalidArgumentException('Missing argument $source["width"].', 400);
        }

        $result = $this->calculateWidthAndHeight($source['width'], $source['height'], $options);

        return $result;
    }

    /**
     * Calculate the resize options for an image resize.
     *
     * @param int $w The source image's width.
     * @param int $h The source image's height.
     * @param array $options Resize options.
     * @return array Returns an array of resizing information.
     * @see \Vanilla\ImageResizer::calculateResize()
     */
    private function calculateWidthAndHeight($w, $h, $options) {
        // In the working variables here the following nomenclature is used:
        // - The "s" and "d" prefixes mean "source" and "destination".
        // - The "w" and "h" suffixes mean "width" and "height".

        $sw = $dw = $w;
        $sh = $dh = $h;
        $sx = 0;
        $sy = 0;

        // First check against absolute height and width.
        $width = empty($options['width']) ? 0 : $options['width'];
        $height = empty($options['height']) ? 0 : $options['height'];

        if ($width && $height) {
            if ($dw >= $width && $dh >= $height) {
                // The source image is larger than the crop so it will end up the same size as the crop.

                // Try cropping the height first.
                $sh = $dw * $height / $width;

                if ($sh > $dh) {
                    // The aspect ratio scales the height too much. Crop the width instead.
                    $sh = $dh;
                    $sw = $dh * $width / $height;
                }

                $dw = $width;
                $dh = $height;
            } elseif ($dw < $width && $dh >= $height) {
                // The width is smaller so recalculate the height.
                $sh = $dh = $this->scale($height, $dw, $width);
            } elseif ($dh < $height && $dw >= $width) {
                // The height is smaller so recalculate the width.
                $sw = $dw = $this->scale($width, $dh, $height);
            } else {
                // The entire image is smaller so recalculate both and choose the best one.
                $h2 = $this->scale($height, $dw, $width);
                $w2 = $this->scale($width, $dh, $height);

                if ($h2 > $dh) {
                    $sw = $dw = $w2;
                } else {
                    $sh = $dh = $h2;
                }
            }
        } elseif ($width && $dw > $width) {
            // Shrink the width, but maintain the aspect ratio.
            $dh = (int)round($dh * $width / $dw);
            $dw = $width;
        } elseif ($height && $dh > $height) {
            // Shrink the height, but maintain the aspect ratio.
            $dw = (int)round($dw * $height / $dh);
            $dh = $height;
        }

        // Adjust the crop to the center.
        if ($sw < $w) {
            $sx = (int)(($w - $sw) / 2);
        }
        if ($sh < $h) {
            $sy = (int)(($h - $sh) / 2);
        }

        return [
            'width' => $dw,
            'height' => $dh,
            'sourceWidth' => $sw,
            'sourceHeight' => $sh,
            'sourceX' => $sx,
            'sourceY' => $sy
        ];
    }

    /**
     * Scale a dimension.
     *
     * @param int $dest The dimension of the destination image. (ex. destination height)
     * @param int $otherSource The other source dimension. (ex. source width)
     * @param int $otherDest The other destination dimension. (ex. destination width)
     * @return int Returns the scaled dimension.
     */
    private function scale($dest, $otherSource, $otherDest) {
        return (int)min(round($dest * $otherSource / $otherDest), $dest);
    }


}
