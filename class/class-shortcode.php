<?php
/**
 * Shortcode Class
 * 
 * @package webtero
 */

declare(strict_types = 1);

namespace WT;

defined( 'ABSPATH' ) || exit;

class Shortcode {	
	/**
	 * Tne Constructor.
	 */
	public function __construct() {
		add_shortcode( 'button', [ $this, '__button' ] );
	}
	
	function __button( $atts, $content = null ) {
		$atts = shortcode_atts(
			array(
				'class'	=> '',
				'link' 	=> '#',
			),
			$atts,
			'button'
		);

		$link 		= esc_url( $atts[ 'link' ] );
		$btn_class 	= sanitize_html_class( $atts[ 'class' ] );
		$label 		= esc_html( $content );

		return sprintf(
			'<a href="%s" class="button %s">%s</a>',
			$link,
			$btn_class,
			$label
		);
	}
}