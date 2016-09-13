<?php

/*
Copyright 2011-2016 Chris Jean & iThemes
Licensed under GPLv2 or above

Version 1.0.3
*/

class PHP_ICO {
	/**
	 * Images in the BMP format.
	 *
	 * @var array
	 * @access private
	 */
	var $_images = array();

	/**
	 * Flag to tell if the required functions exist.
	 *
	 * @var boolean
	 * @access private
	 */
	var $_has_requirements = false;


	/**
	 * Constructor - Create a new ICO generator.
	 *
	 * If the constructor is not passed a file, a file will need to be supplied using the {@link PHP_ICO::add_image}
	 * function in order to generate an ICO file.
	 *
	 * @param string $file Optional. Path to the source image file.
	 * @param array $sizes Optional. An array of sizes (each size is an array with a width and height) that the source image should be rendered at in the generated ICO file. If sizes are not supplied, the size of the source image will be used.
	 */
	function __construct( $file = false, $sizes = array() ) {
		$required_functions = array(
			'getimagesize',
			'imagecreatefromstring',
			'imagecreatetruecolor',
			'imagecolortransparent',
			'imagecolorallocatealpha',
			'imagealphablending',
			'imagesavealpha',
			'imagesx',
			'imagesy',
			'imagecopyresampled',
		);

		foreach ( $required_functions as $function ) {
			if ( ! function_exists( $function ) ) {
				trigger_error( "The PHP_ICO class was unable to find the $function function, which is part of the GD library. Ensure that the system has the GD library installed and that PHP has access to it through a PHP interface, such as PHP's GD module. Since this function was not found, the library will be unable to create ICO files." );
				return;
			}
		}

		$this->_has_requirements = true;


		if ( false != $file )
			$this->add_image( $file, $sizes );
	}

	/**
	 * Add an image to the generator.
	 *
	 * This function adds a source image to the generator. It serves two main purposes: add a source image if one was
	 * not supplied to the constructor and to add additional source images so that different images can be supplied for
	 * different sized images in the resulting ICO file. For instance, a small source image can be used for the small
	 * resolutions while a larger source image can be used for large resolutions.
	 *
	 * @param string $file Path to the source image file.
	 * @param array $sizes Optional. An array of sizes (each size is an array with a width and height) that the source image should be rendered at in the generated ICO file. If sizes are not supplied, the size of the source image will be used.
	 * @return boolean true on success and false on failure.
	 */
	function add_image( $file, $sizes = array() ) {
		if ( ! $this->_has_requirements )
			return false;

		if ( false === ( $im = $this->_load_image_file( $file ) ) )
			return false;


		if ( empty( $sizes ) )
			$sizes = array( imagesx( $im ), imagesy( $im ) );

		// If just a single size was passed, put it in array.
		if ( ! is_array( $sizes[0] ) )
			$sizes = array( $sizes );

		foreach ( (array) $sizes as $size ) {
			list( $width, $height ) = $size;

			$new_im = imagecreatetruecolor( $width, $height );

			imagecolortransparent( $new_im, imagecolorallocatealpha( $new_im, 0, 0, 0, 127 ) );
			imagealphablending( $new_im, false );
			imagesavealpha( $new_im, true );

			$source_width = imagesx( $im );
			$source_height = imagesy( $im );

			if ( false === imagecopyresampled( $new_im, $im, 0, 0, 0, 0, $width, $height, $source_width, $source_height ) )
				continue;

			$this->_add_image_data( $new_im );
		}

		return true;
	}

	/**
	 * Write the ICO file data to a file path.
	 *
	 * @param string $file Path to save the ICO file data into.
	 * @return boolean true on success and false on failure.
	 */
	function save_ico( $file ) {
		if ( ! $this->_has_requirements )
			return false;

		if ( false === ( $data = $this->_get_ico_data() ) )
			return false;

		if ( false === ( $fh = fopen( $file, 'w' ) ) )
			return false;

		if ( false === ( fwrite( $fh, $data ) ) ) {
			fclose( $fh );
			return false;
		}

		fclose( $fh );

		return true;
	}

	/**
	 * Generate the final ICO data by creating a file header and adding the image data.
	 *
	 * @access private
	 */
	function _get_ico_data() {
		if ( ! is_array( $this->_images ) || empty( $this->_images ) )
			return false;


		$data = pack( 'vvv', 0, 1, count( $this->_images ) );
		$pixel_data = '';

		$icon_dir_entry_size = 16;

		$offset = 6 + ( $icon_dir_entry_size * count( $this->_images ) );

		foreach ( $this->_images as $image ) {
			$data .= pack( 'CCCCvvVV', $image['width'], $image['height'], $image['color_palette_colors'], 0, 1, $image['bits_per_pixel'], $image['size'], $offset );
			$pixel_data .= $image['data'];

			$offset += $image['size'];
		}

		$data .= $pixel_data;
		unset( $pixel_data );


		return $data;
	}

	/**
	 * Take a GD image resource and change it into a raw BMP format.
	 *
	 * @access private
	 */
	function _add_image_data( $im ) {
		$width = imagesx( $im );
		$height = imagesy( $im );


		$pixel_data = array();

		$opacity_data = array();
		$current_opacity_val = 0;

		for ( $y = $height - 1; $y >= 0; $y-- ) {
			for ( $x = 0; $x < $width; $x++ ) {
				$color = imagecolorat( $im, $x, $y );

				$alpha = ( $color & 0x7F000000 ) >> 24;
				$alpha = ( 1 - ( $alpha / 127 ) ) * 255;

				$color &= 0xFFFFFF;
				$color |= 0xFF000000 & ( $alpha << 24 );

				$pixel_data[] = $color;


				$opacity = ( $alpha <= 127 ) ? 1 : 0;

				$current_opacity_val = ( $current_opacity_val << 1 ) | $opacity;

				if ( ( ( $x + 1 ) % 32 ) == 0 ) {
					$opacity_data[] = $current_opacity_val;
					$current_opacity_val = 0;
				}
			}

			if ( ( $x % 32 ) > 0 ) {
				while ( ( $x++ % 32 ) > 0 )
					$current_opacity_val = $current_opacity_val << 1;

				$opacity_data[] = $current_opacity_val;
				$current_opacity_val = 0;
			}
		}

		$image_header_size = 40;
		$color_mask_size = $width * $height * 4;
		$opacity_mask_size = ( ceil( $width / 32 ) * 4 ) * $height;


		$data = pack( 'VVVvvVVVVVV', 40, $width, ( $height * 2 ), 1, 32, 0, 0, 0, 0, 0, 0 );

		foreach ( $pixel_data as $color )
			$data .= pack( 'V', $color );

		foreach ( $opacity_data as $opacity )
			$data .= pack( 'N', $opacity );


		$image = array(
			'width'                => $width,
			'height'               => $height,
			'color_palette_colors' => 0,
			'bits_per_pixel'       => 32,
			'size'                 => $image_header_size + $color_mask_size + $opacity_mask_size,
			'data'                 => $data,
		);

		$this->_images[] = $image;
	}

	/**
	 * Read in the source image file and convert it into a GD image resource.
	 *
	 * @access private
	 */
	function _load_image_file( $file ) {
		// Run a cheap check to verify that it is an image file.
		if ( false === ( $size = getimagesize( $file ) ) )
			return false;

		if ( false === ( $file_data = file_get_contents( $file ) ) )
			return false;

		if ( false === ( $im = imagecreatefromstring( $file_data ) ) )
			return false;

		unset( $file_data );


		return $im;
	}
}
