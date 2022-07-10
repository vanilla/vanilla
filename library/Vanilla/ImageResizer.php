<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla;

/**
 * Offers utility methods for resizing image files.
 */
class ImageResizer {

    private const EXIF_ORIENTATION_STANDARD = 1;
    private const EXIF_ORIENTATION_MIRROR = 2;
    private const EXIF_ORIENTATION_ROTATE_180 = 3;
    private const EXIF_ORIENTATION_MIRROR_ROTATE_180 = 4;
    private const EXIF_ORIENTATION_MIRROR_ROTATE_270 = 5;
    private const EXIF_ORIENTATION_ROTATE_270 = 6;
    private const EXIF_ORIENTATION_MIRROR_ROTATE_90 = 7;
    private const EXIF_ORIENTATION_ROTATE_90 = 8;

    /** @var bool */
    protected $alwaysRewriteGif = true;

    /** @var array */
    protected static $typeExt = [
        IMAGETYPE_GIF => 'gif',
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_SWF => 'swf',
        IMAGETYPE_PSD => 'psd',
        IMAGETYPE_BMP => 'bmp',
        IMAGETYPE_TIFF_II => 'tiff',
        IMAGETYPE_TIFF_MM => 'tiff',
        IMAGETYPE_ICO => 'ico',
    ];

    /**
     * This array has all image types that the various image functions can return.
     *
     * This constant is used to provide users with more sensible information. It is NOT an indication of all of the image
     * types supported by this class.
     */
    public const ALL_TYPE_EXT = [
        IMAGETYPE_GIF => 'gif', // 1
        IMAGETYPE_JPEG => 'jpg', // 2
        IMAGETYPE_PNG => 'png', // 3
        IMAGETYPE_SWF => 'swf', // 4
        IMAGETYPE_PSD => 'psd', // 5
        IMAGETYPE_BMP => 'bmp', // 6
        IMAGETYPE_TIFF_II => 'tiff', // 7
        IMAGETYPE_TIFF_MM => 'tiff', // 8
        IMAGETYPE_JPC => 'jpc', // 9
        IMAGETYPE_JP2 => 'jp2', // 10
        IMAGETYPE_JPX => 'jpx', // 11
        IMAGETYPE_JB2 => 'jb2', // 12
        IMAGETYPE_SWC => 'swc', // 13
        IMAGETYPE_IFF => 'iff', // 14
        IMAGETYPE_WBMP => 'wbmp', // 15
        IMAGETYPE_XBM => 'xbm', // 16
        IMAGETYPE_ICO => 'ico', // 17
        IMAGETYPE_WEBP => 'webp', // 18
    ];

    /**
     * Resize an image.
     *
     * @param string $source The path of the source image.
     * @param string|null $destination The path of the destination image. Use `null` for an in-place resize.
     * @param array $options An array of options constraining the image crop.
     *
     * - width: The max width of the destination image.
     * - height: The max height of the destination image.
     * - crop: Whether or not to crop the image to maintain the aspect ratio.
     * @return array Returns an array containing the resize information.
     */
    public function resize($source, $destination, array $options) {
        if (!file_exists($source)) {
            throw new \InvalidArgumentException("Source file \"$source\" does not exist.", 400);
        }

        list($width, $height, $srcType) = getimagesize($source);
        if (!in_array($srcType, [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG])) {
            $ext = $this->extFromImageType($srcType);
            throw new \InvalidArgumentException("Cannot resize images of this type ($ext).", 400);
        }

        if ($destination === null) {
            $destType = $srcType;
            $destination = $source;
        } else {
            if (pathinfo($destination, PATHINFO_EXTENSION) === '*') {
                $destination = substr($destination, 0, -1) . self::$typeExt[$srcType];
            }
            $destType = $this->imageTypeFromExt($destination);
        }

        $exif = $this->exif($source, $srcType);
        $orientation = $exif["Orientation"] ?? null;
        switch ($orientation) {
            case self::EXIF_ORIENTATION_ROTATE_90:
            case self::EXIF_ORIENTATION_MIRROR_ROTATE_90:
            case self::EXIF_ORIENTATION_ROTATE_270:
            case self::EXIF_ORIENTATION_MIRROR_ROTATE_270:
                list($width, $height) = [$height, $width];
                break;
        }

        $resize = $this->calculateResize(['height' => $height, 'width' => $width], $options);

        // Avoid processing images that do not need to be resized, reoriented or converted.
        if (($height <= $resize["height"] && $width <= $resize["width"]) &&
            (!$orientation || $orientation === self::EXIF_ORIENTATION_STANDARD) &&
            $srcType === $destType
        ) {
            $resize = $this->directSave($source, $destination, $resize);
            return $resize;
        }

        try {
            $srcImage = $this->createImage($source, $srcType);
            $srcImage = $this->reorientImage($srcImage, $orientation);

            $destImage = imagecreatetruecolor($resize['width'], $resize['height']);
            if ($srcType === IMAGETYPE_PNG || $srcType === IMAGETYPE_ICO) {
                // Set image transparency on target if necessary
                imagealphablending($destImage, false);
                imagesavealpha($destImage, true);
            }

            imagecopyresampled(
                $destImage,
                $srcImage,
                0,
                0,
                $resize['sourceX'],
                $resize['sourceY'],
                $resize['width'],
                $resize['height'],
                $resize['sourceWidth'],
                $resize['sourceHeight']
            );
            imagedestroy($srcImage); // destroy ASAP

            $this->saveImage($destImage, $destination, $destType, $resize);
        } finally {
            if (is_resource($srcImage)) {
                imagedestroy($srcImage);
            }
            if (is_resource($destImage)) {
                imagedestroy($destImage);
            }
        }

        $resize['path'] = $destination;
        return $resize;
    }

    /**
     * Calculate an image crop based on source image information and options.
     *
     * @param array $source Information about the source image. It should have the following keys:
     *
     * - width: The width of the image.
     * - height: The height of the image.
     * @param array $options An array of options constraining the image crop.
     *
     * - width: The max width of the destination image.
     * - height: The max height of the destination image.
     * - crop: Whether or not to crop the image to maintain the aspect ratio.
     * @return array Returns an array of resizing information.
     *
     * - width: The width of the destination image.
     * - height: The height of the destination image.
     * - sourceX: The starting horizontal location of the source image.
     * - sourceY: The starting vertical position of the source image.
     * - sourceWidth: The sample width of the source image.
     * - sourceHeight: The sample height of the source image.
     * - jpgQuality: The JPEG image quality as a number from 10-100.
     * - pngQuality: The PNG compression level as a number from 0-9.
     * - icoSizes: Additional icon sizes for .ico files as an array in the form `[size|'wxh'|[w, h], ...]`.
     */
    public function calculateResize(array $source, array $options) {
        if (empty($source['height'])) {
            throw new \InvalidArgumentException('Missing argument $source["height"].', 400);
        } elseif (empty($source['width'])) {
            throw new \InvalidArgumentException('Missing argument $source["width"].', 400);
        }

        $result = $this->calculateWidthAndHeight($source['width'], $source['height'], $options);

        $saveOptions = ['jpgQuality', 'pngQuality', 'icoSizes'];
        foreach ($saveOptions as $opt) {
            if (isset($options[$opt])) {
                $result[$opt] = $options[$opt];
            }
        }

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
        $options += [
            'width' => 0,
            'height' => 0,
            'crop' => false,
        ];


        // In the working variables here the following nomenclature is used:
        // - The "s" and "d" prefixes mean "source" and "destination".
        // - The "w" and "h" suffixes mean "width" and "height".
        $dw = $sw = $w;
        $dh = $sh = $h;
        $sx = 0;
        $sy = 0;
        $sratio = $sw / $sh;

        // First check against absolute height and width.
        $width = $options['width'];
        $height = $options['height'];
        $crop = $options['crop'];

        // Calculate the crop if applicable.
        if ($crop) {
            $ratio = $width / $height;

            if ($sratio !== $ratio) {
                // Try cropping the height first.
                $dh = $sh = (int)($w / $ratio);
                if ($sh > $h) {
                    $dw = $sw = (int)($h * $ratio);
                    $dh = $sh = $h;
                    $sx = (int)(($w - $sw) / 2);
                } else {
                    $sy = (int)(($h - $sh) / 2);
                }
            }
        } else {
            $ratio = $sratio;
        }

        // Calculate the scale.
        if ($width && $dw > $width) {
            // Set the width and then scale the height according to the ratio.
            $dw = $width;
            $dh = (int)($dw / $ratio);
        }

        if ($height && $dh > $height) {
            // Set the height and scale the width according to the ratio.
            $dh = $height;
            $dw = (int)($dh * $ratio);
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
     * Direct save a source image to a new destination.
     *
     * @param string $source Source file path.
     * @param string $destination Destination file path.
     * @param array $resize Resizing configuration details.
     * @return array
     */
    private function directSave(string $source, string $destination, array $resize = []): array {
        if ($source !== $destination && copy($source, $destination) === false) {
            throw new \Exception("Unable to save image.");
        }
        $resize["path"] = $destination;
        return $resize;
    }

    /**
     * Return the type-to-extension map.
     *
     * @return array
     */
    public static function getTypeExt() {
        return static::$typeExt;
    }

    /**
     * Return an extension-to-type map.
     *
     * @return array
     */
    public static function getExtType() {
        $extType = array_flip(static::$typeExt);
        if (array_key_exists('jpg', $extType)) {
            $extType['jpeg'] = $extType['jpg'];
        }
        return $extType;
    }

    /**
     * Return all file extensions, including aliases.
     *
     * @return string[]
     */
    final public static function getAllExtensions(): array {
        $extensions = static::getExtType();
        return array_keys($extensions);
    }

    /**
     * Get the image type from a file extension.
     *
     * This is a convenience method for looking up an image based on a mapping of file extension names to image types.
     * This means that the file is never looked at and does not have to exist, allowing you to pick an image type for a
     * destination file.
     *
     * @param string $path The file path to examine.
     * @return int Returns one of the **IMAGETYPE_*** constants.
     */
    public function imageTypeFromExt($path) {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext === 'jpeg') {
            return IMAGETYPE_JPEG;
        }

        $extType = array_flip(self::ALL_TYPE_EXT);
        if (array_key_exists($ext, $extType)) {
            return $extType[$ext];
        }
        throw new \InvalidArgumentException("Unknown image type for extension '$ext'.", 400);
    }

    /**
     * Create a GD image according to type.
     *
     * @param string $path The path of the image.
     * @param int $type One of the __IMAGETYPE_*__ constants.
     * @return resource Returns a GD resource of the image.
     * @throws \Exception Throws an exception if **$type** not a recognized type.
     */
    private function createImage($path, $type) {
        switch ($type) {
            case IMAGETYPE_WBMP:
                $r = imagecreatefromwbmp($path);
                break;
            case IMAGETYPE_GIF:
                $r = imagecreatefromgif($path);
                break;
            case IMAGETYPE_JPEG:
                $r = imagecreatefromjpeg($path);
                break;
            case IMAGETYPE_PNG:
                $r = imagecreatefrompng($path);
                break;
            default:
                $ext = $this->extFromImageType($type);

                throw new \InvalidArgumentException("Could not create image. Invalid type '$ext'.", 400);
        }
        if ($r === false) {
            throw new \Exception("Could not load image.");
        }
        return $r;
    }

    /**
     * Read EXIF data from a supported image.
     *
     * @param string $source Path to an image on the file system.
     * @param string $imageType Type of image being read, identified as one of the IMAGETYPE_* constants.
     * @return array|null
     */
    private function exif(string $source, string $imageType): ?array {
        $result = null;

        if (function_exists("exif_read_data") &&
            in_array($imageType, [IMAGETYPE_JPEG, IMAGETYPE_TIFF_II, IMAGETYPE_TIFF_MM], true)
        ) {
            try {
                $result = exif_read_data($source);
            } catch (\Exception $ex) {
                $result = null;
            }
        }
        return $result;
    }

    /**
     * Get the file extension of an image type.
     *
     * @param int $type One of the __IMAGETYPE_*__ constants.
     * @return string Returns the file extension or **$type** if it was not found.
     */
    public function extFromImageType($type) {
        $ext = self::ALL_TYPE_EXT[$type] ?? (string)$type;
        return $ext;
    }

    /**
     * Rotate an image resource, based on an orientation flag.
     *
     * @param resource $srcImage
     * @param ?int $orientation
     * @return resource
     */
    private function reorientImage($srcImage, ?int $orientation) {
        if (!is_resource($srcImage)) {
            throw new \InvalidArgumentException("Unable to reorient image. Not a valid resource.");
        }

        switch ($orientation) {
            case self::EXIF_ORIENTATION_MIRROR:
                /** @psalm-suppress UndefinedConstant */
                imageflip($srcImage, IMG_FLIP_HORIZONTAL);
                break;
            case self::EXIF_ORIENTATION_ROTATE_180:
                $srcImage = imagerotate($srcImage, 180, 0);
                break;
            case self::EXIF_ORIENTATION_MIRROR_ROTATE_180:
                /** @psalm-suppress UndefinedConstant */
                imageflip($srcImage, IMG_FLIP_VERTICAL);
                break;
            case self::EXIF_ORIENTATION_MIRROR_ROTATE_270:
                $srcImage = imagerotate($srcImage, -90, 0);
                /** @psalm-suppress UndefinedConstant */
                imageflip($srcImage, IMG_FLIP_HORIZONTAL);
                break;
            case self::EXIF_ORIENTATION_ROTATE_270:
                $srcImage = imagerotate($srcImage, -90, 0);
                break;
            case self::EXIF_ORIENTATION_MIRROR_ROTATE_90:
                $srcImage = imagerotate($srcImage, 90, 0);
                /** @psalm-suppress UndefinedConstant */
                imageflip($srcImage, IMG_FLIP_HORIZONTAL);
                break;
            case self::EXIF_ORIENTATION_ROTATE_90:
                $srcImage = imagerotate($srcImage, 90, 0);
                break;
        }
        return $srcImage;
    }

    /**
     * Save an image to disk.
     *
     * @param resource $img The GD resource to save.
     * @param string $path The target path to save to.
     * @param int $type One of the __IMAGETYPE_*__ constants.
     * @param array $options An array of options from **resize()** to pass through to appropriate save methods.
     */
    private function saveImage($img, $path, $type, array $options = []) {
        switch ($type) {
            case IMAGETYPE_GIF:
                imagegif($img, $path);
                break;
            case IMAGETYPE_JPEG:
                imagejpeg($img, $path, empty($options['jpgQuality']) ? 95 : $options['jpgQuality']);
                break;
            case IMAGETYPE_PNG:
                imagepng($img, $path, empty($options['pngQuality']) ? 9 : $options['pngQuality']);
                break;
            case IMAGETYPE_ICO:
                $this->saveIco($img, $path, $options);
                break;
            default:
                $ext = self::extFromImageType($type);
                throw new \InvalidArgumentException("Could not save image. Invalid type '$ext'.", 400);
        }
    }

    /**
     * Save an icon (.ico) file.
     *
     * The GD library cannot save .ico files directly so this method calls out to an external library to do so.
     *
     * @param resource $img The GD resource representing the image.
     * @param string $path The target path of the image.
     * @param array $options An array of options for saving the image. This is passed through from **resize** and looks at the following option:
     *
     * - **icoSizes**: Additional icon sizes for .ico files as an array in the form `[size|'wxh'|[w, h], ...]`.
     */
    private function saveIco($img, $path, $options) {
        $tmpPath = tempnam(sys_get_temp_dir(), 'ico');
        $this->saveImage($img, $tmpPath, IMAGETYPE_PNG, $options);

        $sizes = [];
        if (!empty($options['icoSizes'])) {
            foreach ($options['icoSizes'] as $size) {
                if (is_array($size)) {
                    $sizes[] = $size;
                } elseif (is_int($size)) {
                    $sizes[] = [$size, $size];
                } elseif (preg_match('`^(\d+)x(\d+)$`i', $size, $m)) {
                    $sizes[] = [(int)$m[1], (int)$m[2]];
                }
            }
        }
        // Put original size last so it shows up as the size with getimagesize().
        $sizes[] = [$options['width'], $options['height']];

        $ico = new \PHP_ICO($tmpPath, $sizes);
        try {
            $ico->save_ico($path);
        } finally {
            unlink($tmpPath);
        }
    }

    /**
     * Should GIFs always be rewritten? GIFs will be rewritten if they exceed limits, regardless of this setting.
     * Rewriting animated GIFs will result in loss of animation.
     *
     * @param bool $alwaysRewriteGif
     * @return self
     * @deprecated This is essentially a noop. The target property isn't used.
     */
    public function setAlwaysRewriteGif(bool $alwaysRewriteGif): self {
        $this->alwaysRewriteGif = $alwaysRewriteGif;
        return $this;
    }
}
