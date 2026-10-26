<?php
/**
 * SVG uploads in the Media Library.
 *
 * - Only users who can manage options (administrators) can upload SVGs.
 * - Every uploaded SVG is cleaned: scripts, event handlers (onload=…),
 *   <foreignObject>, external/javascript: links and entities are removed.
 *   A file that can't be read as SVG is rejected.
 * - SVGs show as thumbnails in the Media Library and get width/height.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/** Who may upload SVGs. */
function dlux_svg_allowed() {
	return current_user_can( apply_filters( 'dlux_svg_upload_capability', 'manage_options' ) );
}

/* Allow the MIME type. */
add_filter( 'upload_mimes', function ( $mimes ) {
	if ( dlux_svg_allowed() ) {
		$mimes['svg'] = 'image/svg+xml';
	}
	return $mimes;
} );

/* WordPress can't detect the real type of SVG files, so confirm it by extension. */
add_filter( 'wp_check_filetype_and_ext', function ( $data, $file, $filename, $mimes ) {
	if ( 'svg' === strtolower( pathinfo( (string) $filename, PATHINFO_EXTENSION ) ) && dlux_svg_allowed() ) {
		$data['ext']             = 'svg';
		$data['type']            = 'image/svg+xml';
		$data['proper_filename'] = $data['proper_filename'] ?? false;
	}
	return $data;
}, 10, 4 );

/* Clean every SVG before it is saved. */
add_filter( 'wp_handle_upload_prefilter', function ( $file ) {
	$is_svg = 'svg' === strtolower( pathinfo( (string) ( $file['name'] ?? '' ), PATHINFO_EXTENSION ) );
	if ( ! $is_svg ) {
		return $file;
	}
	if ( ! dlux_svg_allowed() ) {
		$file['error'] = __( 'Sorry, you are not allowed to upload SVG files.', 'dlux' );
		return $file;
	}
	$clean = dlux_svg_sanitize( (string) @file_get_contents( $file['tmp_name'] ) ); // phpcs:ignore
	if ( false === $clean || false === @file_put_contents( $file['tmp_name'], $clean ) ) { // phpcs:ignore
		$file['error'] = __( 'This SVG file could not be read or made safe, so it was not uploaded.', 'dlux' );
	}
	return $file;
} );

/**
 * Remove anything that can run code from an SVG.
 *
 * @return string|false Clean SVG, or false if it isn't valid SVG.
 */
function dlux_svg_sanitize( $svg ) {
	if ( '' === trim( $svg ) || ! class_exists( 'DOMDocument' ) ) {
		return false;
	}
	if ( 0 === strpos( $svg, "\x1f\x8b" ) ) { // gzipped (.svgz)
		$svg = function_exists( 'gzdecode' ) ? (string) @gzdecode( $svg ) : ''; // phpcs:ignore
	}
	// No DOCTYPE / entities (blocks XXE and "billion laughs").
	if ( preg_match( '/<!DOCTYPE|<!ENTITY/i', $svg ) ) {
		$svg = preg_replace( '/<!DOCTYPE[^>\[]*(\[[^\]]*\])?\s*>/is', '', $svg );
		if ( preg_match( '/<!ENTITY/i', $svg ) ) {
			return false;
		}
	}

	$prev = libxml_use_internal_errors( true );
	$dom  = new DOMDocument();
	$ok   = $dom->loadXML( $svg, LIBXML_NONET );
	libxml_clear_errors();
	libxml_use_internal_errors( $prev );
	if ( ! $ok || ! $dom->documentElement || 'svg' !== strtolower( $dom->documentElement->localName ) ) {
		return false;
	}

	$bad_tags = [ 'script', 'foreignobject', 'iframe', 'embed', 'object', 'audio', 'video', 'canvas', 'handler', 'listener', 'set' ];
	$xpath    = new DOMXPath( $dom );

	// Processing instructions (e.g. xml-stylesheet) and comments.
	foreach ( iterator_to_array( $xpath->query( '//processing-instruction() | //comment()' ) ) as $n ) {
		$n->parentNode->removeChild( $n );
	}

	foreach ( iterator_to_array( $dom->getElementsByTagName( '*' ) ) as $el ) {
		if ( ! $el->parentNode ) {
			continue; // already removed with a parent
		}
		if ( in_array( strtolower( $el->localName ), $bad_tags, true ) ) {
			$el->parentNode->removeChild( $el );
			continue;
		}
		foreach ( iterator_to_array( $el->attributes ) as $attr ) {
			$name  = strtolower( $attr->name );
			$value = preg_replace( '/[\s\x00-\x1f]+/', '', strtolower( html_entity_decode( $attr->value, ENT_QUOTES ) ) );
			$remove = 0 === strpos( $name, 'on' )                                                   // event handlers
				|| ( in_array( $name, [ 'href', 'xlink:href', 'src', 'from', 'to', 'values' ], true ) && ( '' !== $value && '#' !== $value[0] ) && ! preg_match( '/^data:image\/(png|jpe?g|gif|webp);/', $value ) )
				|| ( 'style' === $name && preg_match( '/expression|javascript:|url\((?!#)/', $value ) )
				|| ( 'attributename' === $name && preg_match( '/^(on|href|xlink:href)/', $value ) );
			if ( $remove ) {
				$el->removeAttributeNode( $attr );
			}
		}
		// <style> blocks: drop @import and external url().
		if ( 'style' === strtolower( $el->localName ) ) {
			$css = preg_replace( '/@import[^;]+;?|url\(\s*[\'"]?(?!#)[^)]*\)|expression\s*\(|javascript:/i', '', $el->textContent );
			$el->textContent = $css;
		}
	}

	return $dom->saveXML( $dom->documentElement );
}

/* Width/height for SVG attachments, read from the file. */
add_filter( 'wp_generate_attachment_metadata', function ( $meta, $id ) {
	if ( 'image/svg+xml' !== get_post_mime_type( $id ) ) {
		return $meta;
	}
	$size = dlux_svg_size( get_attached_file( $id ) );
	if ( $size ) {
		$meta = is_array( $meta ) ? $meta : [];
		$meta['width']  = $size[0];
		$meta['height'] = $size[1];
		$meta['file']   = _wp_relative_upload_path( get_attached_file( $id ) );
	}
	return $meta;
}, 10, 2 );

/** [ width, height ] from an SVG's width/height or viewBox. */
function dlux_svg_size( $path ) {
	if ( ! $path || ! is_readable( $path ) ) return null;
	$svg = @simplexml_load_file( $path, 'SimpleXMLElement', LIBXML_NONET ); // phpcs:ignore
	if ( ! $svg ) return null;
	$a = $svg->attributes();
	$w = (float) ( $a->width ?? 0 );
	$h = (float) ( $a->height ?? 0 );
	if ( ( ! $w || ! $h ) && ! empty( $a->viewBox ) ) {
		$vb = preg_split( '/[\s,]+/', trim( (string) $a->viewBox ) );
		if ( 4 === count( $vb ) ) {
			$w = (float) $vb[2];
			$h = (float) $vb[3];
		}
	}
	return ( $w && $h ) ? [ (int) round( $w ), (int) round( $h ) ] : null;
}

/* Media Library grid/modal: use the SVG itself as the preview. */
add_filter( 'wp_prepare_attachment_for_js', function ( $response, $attachment ) {
	if ( 'image/svg+xml' !== ( $response['mime'] ?? '' ) ) {
		return $response;
	}
	$url  = wp_get_attachment_url( $attachment->ID );
	$size = dlux_svg_size( get_attached_file( $attachment->ID ) ) ?: [ 512, 512 ];
	$full = [ 'url' => $url, 'width' => $size[0], 'height' => $size[1], 'orientation' => $size[1] > $size[0] ? 'portrait' : 'landscape' ];
	$response['image']  = [ 'src' => $url, 'width' => $size[0], 'height' => $size[1] ];
	$response['thumb']  = [ 'src' => $url, 'width' => 150, 'height' => 150 ];
	$response['sizes']  = [ 'full' => $full, 'medium' => $full, 'thumbnail' => $full, 'large' => $full ];
	$response['width']  = $size[0];
	$response['height'] = $size[1];
	return $response;
}, 10, 2 );

/* Front end: wp_get_attachment_image() needs a size for SVGs. */
add_filter( 'wp_get_attachment_image_src', function ( $image, $id ) {
	if ( is_array( $image ) && 'image/svg+xml' === get_post_mime_type( $id ) && ( empty( $image[1] ) || empty( $image[2] ) ) ) {
		$size = dlux_svg_size( get_attached_file( $id ) );
		if ( $size ) {
			$image[1] = $size[0];
			$image[2] = $size[1];
		}
	}
	return $image;
}, 10, 2 );

/* Admin: SVG thumbnails fill their box. */
add_action( 'admin_head', function () {
	echo '<style>.attachment-266x266[src$=".svg"],.thumbnail img[src$=".svg"],.media-icon img[src$=".svg"]{width:100%!important;height:auto!important}</style>';
} );
