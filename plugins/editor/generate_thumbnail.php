<?php

if (!function_exists('generate_thumbnail')):
   /**
    *
    * @param string $src Source of image file
    * @param string $dst Destination to save manipulated image file
    * @param array $opts Options: read the $default_opts notes
    *
    * Note: At minimum, a $src must be provided. All other parameters are optional.
    *       This function handles just about every weirdness you can throw at it.
    *       It will attempt to fail as gracefully as possible, to try and produce
    *       an image.
    *
    *       If both width and height are defined, then image will take those
    *       exact dimensions. If only a width or a height is provided, then
    *       the side that was specified will be the side that controls, while
    *       the other side will maintain its aspect ratio. This is also true for
    *       crop options; read more about those below.
    *
    * @TODO:
    * - for alpha pngs look at these functions:
    *          imagealphablending($TargetImage, false);
    *          imagesavealpha($TargetImage, true);
    *
    * - Function currently handles JPEG, PNG, and GIF. Handle others.
    *
    * - Allow passing an array of widths and / or heights, which will generate
    *   multiple thumbnails, so that the imagecreatefrom* function can be recycled
    *   for every iteration, which is costly. Optionally, can pass the file
    *   pointer to itself, and recursively resize ensuing images.\
    *
    *   is_resource($image) return true;
    *   get_resource_type($image) returns 'gd';
    *
    *
    *
    * Note: the result_* values, like result_width, etc, may contain decimal
    * numbers as rounding is senseless when it comes to resizing. Resizing
    * can and should use whatever exact number, decimals included, are calculated.
    * What should be kept in mind is when grabbing the return payload and
    * using it for other applications, like inserting into a DB. The numbers
    * will probably need to be rounded.
    *
    * @php <5.3 Due to anonymous functions
    * @author Dane MacMillan
    */
   function generate_thumbnail($src, $dst = '', $opts = array()) {
      // All data being used in this function goes here, so that it can be easily
      // printed out for debugging purposes. The size of this array will
      // depend on how far into the checks it gets, so the values returned are
      // also an indication of the images validity.
      $data = array();

      // Anonymous function to translate a human-readable value like
      // 128M, 1G, or 768K to bytes. This is used against php.ini values.
      //
      // NOTE:
      // -----
      // 1KB = 1024b
      // 1MB = 1024b * 1024b
      // 1GB = 1024b * 1024b * 1024b
      // This switch statement will trickle down from its proper unit,
      // as no breaks.
      $bytes_from = function ($human_readable_size) {
         $size = trim($human_readable_size);
         switch (strtolower(substr($size, -1))) {
            case 'g':
               $size *= 1024;
            case 'm':
               $size *= 1024;
            case 'k':
               $size *= 1024;
         }

         return $size;
      };

      // Notice: Undefined offset: -9223372036854775808 in /var/www/frontend/plugins/editor/generate_thumbnail.php on line 85
      // Translate bytes to human readable format. Use for php.ini values.
      // Based off of Chris Jester-Young's implementation.
      $bytes_to = function ($byte_size, $precision = 0) {
         $base = log($byte_size) / log(1024);
         $suffixes = array('B', 'k', 'M', 'G', 'T');
         $floorbase = (floor($base) >= 0)
            ? floor($base)
            : 0;
         return round(pow(1024, $base - $floorbase), $precision).$suffixes[$floorbase];
      };

      // Set some hard limits, so function will not process image if the
      // options provided are unreasonable. These can also be used as defaults,
      // though make sure to check against the hard value variables, as the
      // defaults can be overwritten.
      $data['hard_limits'] = array(
         'max_megapixels' => 10,
         'min_dimension' => 0, // If 0 and no dims defined, source dims used
         'php_memory_limit' => ini_get('memory_limit'),
         'php_post_max_size' => ini_get('post_max_size'), // Not used
         'php_upload_max_filesize' => ini_get('upload_max_filesize'), // Not used
         'max_filesize_bytes' => 20 * 1024 * 1024, // 20MB
         'quality' => 85 // Not really a hard limit, but default store
      );

      // Rely on PHP's ini values for maxes, but if max_filesize_bytes defined
      // in the opts array passed to function, use that. The max_filesize_bytes
      // defined in the hard_limits array is simply for curbing crazy limits.
      $max_filesize_bytes = min(
         $bytes_from($data['hard_limits']['php_post_max_size']),
         $bytes_from($data['hard_limits']['php_upload_max_filesize']),
         $data['hard_limits']['max_filesize_bytes']
      );

      // Default options--definitive list. If options are passed that do not have
      // a default here, they will be removed.
      $default_opts = array(
         // Provide a width or a height. If only one provided, that side controls,
         // while the other side maintains its relative aspect ratio. If both
         // are defined, the image will take those exact dimensions, ignoring
         // the maintenance of an aspect ratio.
         'width' => 0,
         'height' => 0,
         // Valid crop parameters:
         //
         // X/Y coordinates: '400 300'
         // Words, like CSS: 'top left', 'center bottom', 'center'
         // Combined: '100 center', 'top 300'
         // Only the exact X Y coordinates observe strict order. Words and
         // combined does not care what order they're written in.
         'crop' => '',
         // max-* parameters have not been coded in yet, but they will.
         'max_width' => '',
         'max_height' => '',
         // Quality of image produced, e.g., 85
         // Note that PNGs use a compression level 0-9 to determine quality, so
         // whatever pecentage 1-100 (for JPEG) is passed, it will be converted
         // to compression_level for PNGs. Read further down.
         // The logic to pass a compression_level directly is not coded. It's
         // easier to just provide a percentage, and if PNG, auto-convert it.
         'quality' => $data['hard_limits']['quality'],
         // Do not allow images beyond the specified megapixels value, e.g., 10.
         'max_megapixels' => $data['hard_limits']['max_megapixels'],
         // Will check if different than PHP's memory_limit, and if so,
         // it will ini_set the new value.
         'max_memory_bytes' => $bytes_from($data['hard_limits']['php_memory_limit']),
         // Don't let files exceed this value.
         'max_filesize_bytes' => $max_filesize_bytes,
         // Setting to true will dump the available EXIF metadata into the
         // returned $data array.
         'exif' => false,
         // Do not allow thumbnails to exceed dimensions of original photo.
         'allow_bigger' => false,
         // If debug is true, all $data calculations will happen, but the image
         // creation and processing will not happen (so save major memory). This
         // is useful if you just want to know how much memory a photo will
         // require, or what the result dimensions will be.
         'debug' => false
      );

      // Combine user-provided options with defaults, remove any non-valid options.
      // Also, if for some reason the $opts array is fed with valid properties but
      // empty values, the array_filter will remove them, to insure that the
      // $default_opts are passed in.
      $data['options'] = array_intersect_key(array_filter($opts) + $default_opts, $default_opts);

      // If function called with specific max_memory_bytes, set the ini value.
      // This will check if the hard_limits php_memory_limit is the same as the
      // default max_memory_bytes, and if it's not, it's because the function
      // was supplied a custom max_memory_bytes
      if ($data['options']['max_memory_bytes'] != $bytes_from($data['hard_limits']['php_memory_limit'])) {
         $php_memory_limit = $bytes_to($data['options']['max_memory_bytes']);
         ini_set('memory_limit', $php_memory_limit);
         // Redefine hard limit for new PHP memory_limit setting
         $data['hard_limits']['php_memory_limit'] = $php_memory_limit;
      }

      // Image processing needs all the resources it can get.
      unset($default_opts);

      // If image was processed successfully
      $data['success'] = false;


      /**
       * Preliminary step: perform sanity checks against values provided to
       * function before even considering checks against the file itself. Also
       * check to make sure image is not too big.
       */

      $data['gd'] = (!function_exists('gd_info'))
         ? false
         : true;

      // Get byte size of image, and check if src file actually exists.
      $data['file_exists'] = false;
      $data['filesize_bytes'] = 0;
      if (file_exists($src)) {
         $data['file_exists'] = true;
         $data['filesize_bytes'] = filesize($src);
      }

      // If this remains false, then person who called the function provided it
      // some unreasonable options.
      $data['hard_limits_respected'] = false;

      // First determine whether src file exists, then check if hard limits were
      // respected when calling function.
      // Also, if the dimensions provided are false, the function will stop.
      if ($data['file_exists']
         && ($data['options']['width'] >= $data['hard_limits']['min_dimension']
            || $data['options']['height'] >= $data['hard_limits']['min_dimension'])
         && $data['options']['max_megapixels'] >= $data['hard_limits']['max_megapixels']
         && $data['filesize_bytes']
         && $data['filesize_bytes'] <= $data['hard_limits']['max_filesize_bytes']
         && $data['options']['max_filesize_bytes'] <= $data['hard_limits']['max_filesize_bytes']
      ) {
         $data['hard_limits_respected'] = true;
      }

      // If image quality is incorrect, use the default.
      if (!is_numeric($data['options']['quality'])
         || !($data['options']['quality'] >= 10)
         || !($data['options']['quality'] <= 100)
      ) {
         $data['options']['quality'] = $data['hard_limits']['quality'];
      }

      /**
       * First step: actual checks against the file itself. Make sure it can be
       * worked on, mime-wise, megapixel-wise, memory-wise. Also, configure
       */

      // Check allowed file extensions, but also use this array against their
      // extracted mime subtypes, as any file type could be given these extensions.
      // Not all src files necessarily have an extension. For example, PHP creates
      // a temporary file name with no extension, so allow that through. It will
      // get caught later if it's not an image.
      $data['allowed_files'] = array('jpg', 'jpeg', 'gif', 'png', 'bmp', 'ico', '');
      $data['file'] = $src;

      // Get path info for source
      $src_path_data = pathinfo($data['file']);
      $data['extension'] = $src_path_data['extension'];

      // Determine if destination path ends with a forward slash. If so, that
      // means the filename gets built by the script.
      $dst_is_dir = (substr($dst, -1) == '/')
         ? true
         : false;

      // Get path info for destination, if any.
      $dst_path_data = pathinfo($dst);

      // Set destination path. If empty, then saves to the current working dir.
      // Optionally, let it save to actual location of included script with __DIR__
      $data['dst_directory'] = (isset($dst_path_data['dirname']))
         ? $dst_path_data['dirname']
         : getcwd();

      // If $dst_is_dir is true, then not only was a destination provided,
      // but it was provided without a filename, so adjust the path for this.
      if ($dst_is_dir) {
         // If dirname is exactly a forward slash, then this is root, and so
         // concatenating just below for dst_directory would create a path
         // that begins with two forward slashes, e.g., //foo, instead of /foo,
         // so clear it to make the concatenation easier.
         if ($dst_path_data['dirname'] == '/') {
            $dst_path_data['dirname'] = '';
         }

         // Adjust directory path, as providing a destination ending in a forward
         // slash is ignored by pathinfo, and that last directory is interpreted
         // as filename instead.
         $data['dst_directory'] = $dst_path_data['dirname'].'/'.$dst_path_data['basename'];

         // Clear stored filename, as it's incorrectly been assigned the name
         // of dirname or basename. This means no filename was actually provided
         // by dst, so by clearing it, the name will be auto-generated by the
         // ensuing logic, which will assign the src name to it, plus timestamp
         // and quality properties.
         $dst_path_data['filename'] = '';
      }

      // Check if dst exists, if not create, then check if it's writable. It
      // most likely always be writable, due to 777, unless trying to write
      // to root-level directories.
      $data['dst_writable'] = true;
      if (!file_exists($data['dst_directory'])
         && !mkdir($data['dst_directory'], 0777, true)
         && !is_writable($data['dst_directory'])
      ) {
         $data['dst_writable'] = false;
      }

      // If no dst is provided, use the path set above, but use original file
      // name, and add timestamp and quality percentage.
      $data['dst_filename'] = ($dst_path_data['filename'] != '')
         ? $dst_path_data['filename']
         : $src_path_data['filename'].'.'.time().'-'.$data['options']['quality'];

      // This will receive a final check further down, after the mime_subtype is
      // extracted from the file.
      $data['dst_extension'] = (isset($dst_path_data['extension']))
         ? $dst_path_data['extension']
         : $src_path_data['extension'];

      // Don't need these anymore.
      unset($src_path_data);
      unset($dst_path_data);

      // Determine if we can proceed to image calculations step (correcting
      // dimensions, aspect ratios, and crop values. The checks below will return
      // true if they pass.
      $data['calculate_image_values'] = false;

      // Start by verifying that GD is available, and that the destination
      // directory for the thumbnail exists and is writable. This is followed by
      // a basic file extension check, and if it passes, immediately
      // get image info, assign it, and check if it's valid (array).
      if ($data['gd']
         && $data['dst_writable']
         && $data['hard_limits_respected']
         && in_array(strtolower($data['extension']), $data['allowed_files'])
         && is_array($img = getimagesize($data['file']))
      ) {

         // Get type (e.g., image) and subtype (e.g., png)
         list($data['mime_type'], $data['mime_subtype']) = explode('/', strtolower($img['mime']));

         // If src file extension empty, there's a chance this is a temporary PHP
         // file, as they have no extensions, so check if empty, and use
         // mime_subtype provided, if any, otherwise provide it with an unknown
         // subtype and it will get caught in a few lines and the function will
         // stop.
         if (!$data['extension'] && !$data['mime_subtype']) {
            $data['extension'] = 'unknown';
         }

         // Get dimensions
         $data['width'] = $img[0];
         $data['height'] = $img[1];

         // Get orientation
         $data['orientation'] = ($data['width'] < $data['height'])
            ? 'portrait'
            : 'landscape';

         // Get total pixels for the image
         $data['pixels'] = $data['width'] * $data['height'];

         // Get megapixels
         $data['megapixels'] = ($data['pixels']) / 1000000;

         // Perform more accurate check against the image's validity.
         if ($data['mime_type'] == 'image'
            && in_array(strtolower($data['mime_subtype']), $data['allowed_files'])
         ) {

            // Safe to continue working with image.

            // Determine for sure the correct filename extension, and make sure it's
            // allowed, same with the source extension. If unavailable, use the
            // mime_subtype. In the case of jpg, it will always be jpeg, but this
            // will just indicate that the src and/or dst file had no file extension.
            // The second is only used if the extension was not provided by the
            // dst in dst_extension.
            $data['dst_extension'] = ($data['dst_extension'] && in_array(strtolower($data['dst_extension']), $data['allowed_files']))
               ? $data['dst_extension']
               : $data['mime_subtype'];

            // Get bytes per pixel by checking the channels of image. If there are
            // none default to 4, just in case. getimagesize does not always
            // return bits and channels.
            //
            // NOTE:
            // -----
            // RGB = 3 bytes, RGBA = 4 bytes
            // 1 pixel = 4 bytes (rgba) = 3 channels (+ 1 channel) * 8 bits = 32 bits
            $data['bytes_per_pixel'] = (isset($img['channels']))
               ? $img['channels']
               : 4;

            // Don't need this anymore
            unset($img);

            // PHP memory limit overhead to account for any unknowns. Read thread
            // http://php.net/manual/en/function.imagecreatefromjpeg.php
            // Makes sure there is always just a bit more available in case.
            $data['memory_pad'] = 1.7;

            // Approximate memory in bytes this image will require to process. If
            // This is still not enough, pad by max 1.7.
            $data['memory_required_bytes'] = $data['pixels'] * $data['bytes_per_pixel'] * $data['memory_pad'];

            // Determine if file is going to be too heavy to process, but
            // remember that the thumbnail must be included as well, which almost
            // doubles the memory usage. This is done further down. The first
            // check is here in case the original alone is massive.
            if ($data['memory_required_bytes'] < $data['options']['max_memory_bytes']) {
               $data['calculate_image_values'] = true;

               // Get SHA1 of original file
               $data['sha1_file'] = sha1_file($data['file']);

               // Get EXIF metadata, if option true
               if ($data['options']['exif']
                  && extension_loaded('exif')
               ) {
                  $data['exif'] = exif_read_data($data['file'], 'EXIF');
               }
            }
         }
      }


      /**
       * Second step: image calculations begin here. Now that all the above
       * checks were performed, it's certain that the file provided is an image
       * we can handle, so begin the second step of calculating the correct
       * values for image dimensions, aspect ratio, orientation, and crop values.
       */

      // If the calculations below are correct, proceed to image processing.
      $data['image_start_processing'] = false;

      if ($data['calculate_image_values']) {

         // Is the resulting image going to maintain aspect ratio (dependent on
         // controlling side provided), or take the exact dimensions provided (both
         // values provided)?

         // By default, thumbnails generated cannot have dimensions larger
         // than the original, so adjust the provided values.
         if (!$data['options']['allow_bigger']) {

            // If width provided is larger than original, reset to 0.
            if ($data['options']['width'] > $data['width']) {
               $data['options']['width'] = 0;
            }

            // If height provided is larger than original, reset to 0.
            if ($data['options']['width'] > $data['width']) {
               $data['options']['height'] = 0;
            }
         }

         // Create to exact dimensions (aspect ratio not guaranteed).
         if ($data['options']['width'] && $data['options']['height']) {
            $data['result_width'] = $data['options']['width'];
            $data['result_height'] = $data['options']['height'];
         } else {
            // Only one dimension length provided, so create with aspect ratio
            // guaranteed.
            if ($data['options']['width']) {
               $data['result_width'] = $data['options']['width'];
               $data['result_height'] = $data['result_width'] * $data['height'] / $data['width'];
            } elseif ($data['options']['height']) {
               $data['result_height'] = $data['options']['height'];
               $data['result_width'] = $data['result_height'] * $data['width'] / $data['height'];

               // No width or height provided, so do not resize. Return original
               // dimensions, with the modified quality, and consequently all EXIF
               // metadata stripped. A photo will not get manipulated at this point
               // if the hard limit for min_dimension is defined.
            } else {
               $data['result_height'] = $data['height'];
               $data['result_width'] = $data['width'];
            }
         }

         // Determine result orientation. If different from original orientation,
         // it's possible that the image will be skewed.
         $data['result_orientation'] = ($data['result_width'] < $data['result_height'])
            ? 'portrait'
            : 'landscape';


         // TODO max dimensions logic

         // Check against max-width and max-height. If either are larger, size
         // down the max controlling side.

         // Q: How to determine which side controls if both max-width and
         // max-height defined and are both relevant due to result width and
         // height being larger in each. CSS does not handle this too well by
         // default.
         //
         // A: Determine based on orientation. If it's landscape select
         // max-width, if portrait select max-height.
         //
         // NOTE: perhaps instead of readjusting the result dimensions, just crop
         // it to the max dimensions.

         if ($data['options']['max_width'] && $data['options']['max_height']) {
            //echo 'maxes';

            // If image dimensions pass one or both of the max-* dimensions, then
            // center crop it with the new crop coords provided. If crop coords
            // are already defined, they will get overwritten here.
            //$data['options']['crop']
         } else {
            if ($data['width'] > $data['options']['max_width']) {

            } elseif ($data['height'] > $data['options']['max_height']) {

            }
         }

         // Handle cropping coordinates and position, if any, allow for words to
         // position the crop area. Note, strict X Y order must be observed if
         // using numbers as coordinates. If simply providing one coordinate
         // position (either number or word), the second will default to
         // center. If defining both coordinates as words, the order doesn't
         // matter. Just write "top left" "left top" "bottom center" etc.
         $crop_coords = trim($data['options']['crop']);

         // If there are crop coordinates provided, it doesn't necessarily
         // mean it's croppable. The values can be very wrong or pushing limits.
         $data['croppable'] = false;

         if ($crop_coords != '') {

            // If words are used to describe crop position, map them accordingly,
            // otherwise the exact x y position will be used.
            $crop_map_x = array(
               'left' => 0,
               'center' => ($data['width'] - $data['result_width']) / 2,
               'right' => $data['width'] - $data['result_width']
            );

            $crop_map_y = array(
               'top' => 0,
               'center' => ($data['height'] - $data['result_height']) / 2,
               'bottom' => $data['height'] - $data['result_height']
            );

            // Get crop coordinates
            $crop_coords = explode(' ', $crop_coords);

            // Determine if one or two provided
            if (count($crop_coords) == 2) {
               $data['crop_x'] = $crop_coords[0];
               $data['crop_y'] = $crop_coords[1];

               // If both coordinates were words, let's not care about whether
               // they were ordered x and y, just check against the crop maps and
               // manually order them properly, so that user does not have to
               // remember a tedious spec like order of arguments.
               if (array_key_exists($crop_coords[0], $crop_map_x)) {
                  $data['crop_x'] = $crop_coords[0];
                  $data['crop_y'] = $crop_coords[1];
               } elseif (array_key_exists($crop_coords[0], $crop_map_y)) {
                  $data['crop_x'] = $crop_coords[1];
                  $data['crop_y'] = $crop_coords[0];
               }
            } else {
               // If only one crop coordinate provided, then it's interpreted as x
               // position, and the second (y) will default to center.
               $data['crop_x'] = $crop_coords[0];
               $data['crop_y'] = 'center';

               // If the one coordinate provided was a word, let's not care about
               // whether it was x or y, just check against the crop maps and
               // define the other one as center, as above.
               if (array_key_exists($crop_coords[0], $crop_map_x)) {
                  $data['crop_x'] = $crop_coords[0];
                  $data['crop_y'] = 'center';
               } elseif (array_key_exists($crop_coords[0], $crop_map_y)) {
                  $data['crop_x'] = 'center';
                  $data['crop_y'] = $crop_coords[0];
               }
            }

            //echo "{$data['crop_x']}, {$data['crop_y']}";

            // Map words to actual coordinates if numbers not provided.
            if (!is_numeric($data['crop_x'])) {
               $data['crop_x'] = $crop_map_x[$data['crop_x']];
            }

            if (!is_numeric($data['crop_y'])) {
               $data['crop_y'] = $crop_map_y[$data['crop_y']];
            }

            // Just be sure the crop coordinates are valid numbers, and
            // are realistic (e.g., crop position + dimension length does not
            // exceed original image boundaries).
            if ((intval($data['crop_x']) >= 0 && intval($data['crop_y']) >= 0)
               && (($data['crop_x'] + $data['options']['width']) <= $data['width'])
               && (($data['crop_y'] + $data['options']['height']) <= $data['height'])
            ) {
               $data['croppable'] = true;
            }
         }

         // Must check memory again, because this time we now know the total
         // memory to be consumed, based off of original and resized.
         //
         // Determine memory required for new image (add to original total)
         $data['result_pixels'] = $data['result_width'] * $data['result_height'];
         $data['memory_required_bytes'] += $data['result_pixels'] * $data['bytes_per_pixel'] * $data['memory_pad'];
         $data['memory_required'] = $bytes_to($data['memory_required_bytes'], 10);

         // If this check fails, there is insufficient memory to process image.
         if ($data['memory_required_bytes'] < $data['options']['max_memory_bytes']) {
            // We can start the last step of actually processing the image.
            $data['image_start_processing'] = true;
         }
      }


      /**
       * Third and final step: process the image. This is where memory consumption
       * will increase dramatically.
       */

      if ($data['image_start_processing'] && !$data['options']['debug']) {

         // Before processing image, grab the memory being used up to now, then
         // subtract it from the check below, which will provide the actual memory
         // used just for photo processing.
         $data['memory_peak_usage_script_bytes'] = memory_get_peak_usage(false);
         $data['memory_peak_usage_script'] = $bytes_to($data['memory_peak_usage_script_bytes'], 10);

         // Create identifier for provided image. Let the memory consumption begin.
         // Use dynamic function call.
         $imagecreatefrom = "imagecreatefrom{$data['mime_subtype']}";
         $image = $imagecreatefrom($data['file']);

         // Now that the extension is absolutely known, adjust quality level for
         // PNGs, as they have a compression level of 0-9, not a percentage of
         // 1-100 like with JPEGs.
         // Note: http://www.php.net/manual/en/function.imagepng.php read the
         // notes about compression, in particular, that 0 is best, 9 worst.
         // However, upon testing, there is no discernible difference is size or
         // quality, regardless of the compression_level set.
         $data['options']['compression_level'] = floor(($data['options']['quality'] / 10) - 1);

         // Finally put the whole destination path together.
         $data['dst'] = $data['dst_directory'].'/'.$data['dst_filename'].'.'.$data['dst_extension'];

         // Create canvas for new image.
         $canvas = imagecreatetruecolor($data['result_width'], $data['result_height']);

         // Crop
         if ($data['croppable']) {
            // These both produce the same image, but resampling should produce
            // better results, especially on smaller images, as it smoothly
            // interpolates pixels values. From tests, they both produce
            // identical, down to the byte size, images, with no discernable
            // difference, even in max processing power. However, the docs state
            // resampling is better.

            // Regular copy crop
            //imagecopy($canvas, $image, 0, 0, $data['crop_x'], $data['crop_y'], $data['width'], $data['height']);

            // Resampled copy crop, note the additional result dimensions, third
            // and fourth from last. src and dst dimensions must equal.
            imagecopyresampled($canvas, $image, 0, 0, $data['crop_x'], $data['crop_y'], $data['result_width'], $data['result_height'], $data['result_width'], $data['result_height']);

            // Just resize
         } else {
            imagecopyresampled($canvas, $image, 0, 0, 0, 0, $data['result_width'], $data['result_height'], $data['width'], $data['height']);
         }

         // Create image, based on destination extension, default to
         // src mime_subtype.
         switch ($data['mime_subtype']) {
            case 'jpeg':
               imagejpeg($canvas, $data['dst'], $data['options']['quality']);
               break;
            case 'gif':
               imagegif($canvas, $data['dst']);
               break;
            case 'png':
               // Note the use of compression_level instead
               imagepng($canvas, $data['dst'], $data['options']['compression_level']);
               break;
         }

         // Immediately free up canvas memory
         imagedestroy($canvas);

         // Free up template / original / source image. If there are multiple
         // image resize dimensions passed, keep this original in memory, instead
         // of creating and destroying it, calling the function multiple times.
         imagedestroy($image);

         // Get SHA1 of result file
         $data['result_sha1_file'] = sha1_file($data['dst']);

         // Return result size in bytes, for debug.
         $data['result_filesize_bytes'] = filesize($data['dst']);

         // Image manipulation took this much memory. Record for debug purposes.
         $memory_peak_usage_total_bytes = memory_get_peak_usage(false);
         $data['memory_peak_usage_image_bytes'] = $memory_peak_usage_total_bytes - $data['memory_peak_usage_script_bytes'];
         $data['memory_peak_usage_image'] = $bytes_to($data['memory_peak_usage_image_bytes'], 10);
         $data['memory_peak_usage_total_bytes'] = $memory_peak_usage_total_bytes;
         $data['memory_peak_usage_total'] = $bytes_to($memory_peak_usage_total_bytes, 10);

         // Image processing was successful
         $data['success'] = true;
      }

      if ($data['options']['debug']) {
         $data['success'] = 'debug';
      }

      // This will contain a lot of debugging information. Check that `success`
      // is true, but use type comparison (===), because if this function is
      // run in debug mode, the value of `success` will be `debug`.
      return $data;
   }
endif;
