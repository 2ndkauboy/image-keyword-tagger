<?php
/**
 * Plugin Name: Image Keyword Tagger
 * Description: Adds tags to an uploaded image when there are tags in the EXIF or IPTC data.
 * Version: 1.0.0
 * Author: Bernhard Kau
 * Author URI: http://kau-boys.de
 * Plugin URI: https://github.com/2ndkauboy/image-keyword-tagger
 * Text Domain: taxonomy-gallery
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0
 */

function image_keyword_tagger_add_tags_to_attachments() {
	register_taxonomy_for_object_type( 'post_tag', 'attachment' );
}
add_action( 'init' , 'image_keyword_tagger_add_tags_to_attachments' );

function image_keyword_tagger_extract_exif_keywords( $meta, $file, $sourceImageType, $iptc ) {
	$exif = @exif_read_data( $file, 'IFD0', true );

	if ( ! empty( $exif['IFD0']['Keywords'] ) ) {
		foreach ( explode( ';', image_keyword_tagger_fix_encoding( $exif['IFD0']['Keywords'] ) ) as $keyword ) {
			$meta['keywords'][] = $keyword;
		}
	}

	if ( empty( $meta['title'] ) && ! empty( $exif['IFD0']['Title'] ) ) {
		$meta['title'] = image_keyword_tagger_fix_encoding( $exif['IFD0']['Title'] );
	}

	if ( empty( $meta['copyright'] ) && ! empty( $exif['IFD0']['Copyright'] ) ) {
		$meta['copyright'] = image_keyword_tagger_fix_encoding( $exif['IFD0']['Copyright'] );
	}

	return $meta;
}
add_filter( 'wp_read_image_metadata', 'image_keyword_tagger_extract_exif_keywords', 10, 4 );

function image_keyword_tagger_add_keywords( $mid, $object_id, $meta_key, $_meta_value ) {
	if ( '_wp_attachment_metadata' == $meta_key ) {
		$attachment_meta = wp_get_attachment_metadata( $object_id );

		if ( ! empty( $attachment_meta['image_meta']['keywords'] ) ) {
			wp_set_post_tags( $object_id, implode( ',', $attachment_meta['image_meta']['keywords'] ), true );
		}
	}
}
add_action( 'added_post_meta', 'image_keyword_tagger_add_keywords', 10, 4 );

function image_keyword_tagger_fix_encoding( $str ) {

	$encodings = array(
		'UTF-8', 'ASCII',
		'ISO-8859-1', 'ISO-8859-2', 'ISO-8859-3', 'ISO-8859-4', 'ISO-8859-5',
		'ISO-8859-6', 'ISO-8859-7', 'ISO-8859-8', 'ISO-8859-9', 'ISO-8859-10',
		'ISO-8859-13', 'ISO-8859-14', 'ISO-8859-15', 'ISO-8859-16',
		'Windows-1251', 'Windows-1252', 'Windows-1254',
	);

	$detected_encoding = mb_detect_encoding( $str, $encodings );

	if ( $detected_encoding != 'UTF-8' ) {
		$str = iconv( $detected_encoding, 'UTF-8', $str );
	}

	return str_replace( chr( 0 ), '', $str );
}