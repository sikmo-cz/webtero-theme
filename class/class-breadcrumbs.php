<?php
/**
 * Bread Crumbs Class
 * 
 * @package webtero
 */

declare(strict_types = 1);

namespace WT;

defined( 'ABSPATH' ) || exit;

class Breadcrumbs {
	/**
	 * Constructor
	 * Generates breadcrumb navigation
	 */
	public function __construct() {
		return self::get();
	}

	/**
	 * Generate breadcrumb HTML
	 *
	 * @param bool $skipHomepage Whether to skip homepage in breadcrumb trail
	 * @return string Breadcrumb HTML
	 */
	private static function get( bool $skipHomepage = true ): string 
	{
		ob_start();

		echo '<ul aria-label="breadcrumb">';
	
		if ( $skipHomepage === true ) {
			echo '<li><a href="' . home_url() . '">' . get_the_title( get_option( 'page_on_front' ) ) . '</a></li>';
		}
	
		if (is_page()) {
			$post = get_post();

			// Check if the current page has a parent
			if ($post->post_parent) {
				$parent_id  = $post->post_parent;
				$breadcrumbs = array();

				while ($parent_id) {
					$page = get_page($parent_id);
					$breadcrumbs[] = '<li><a href="' . get_permalink($page->ID) . '">' . get_the_title($page->ID) . '</a></li>';
					$parent_id  = $page->post_parent;
				}

				$breadcrumbs = array_reverse($breadcrumbs);

				foreach ($breadcrumbs as $crumb) {
						echo $crumb;
				}
			}

			// Current page
			echo '<li>' . get_the_title() . '</li>';
		} elseif (is_single()) {
			// Single page
			echo '<li><a href="' . esc_url( get_post_type_archive_link(get_post_type(get_the_ID())) ) . '">' . esc_html( get_post_type_object( get_post_type(get_the_ID()) )->labels->name ) . '</a></li>';
			echo '<li>' . get_the_title() . '</li>';
		} elseif (is_home()) {
			// Blog page
			echo '<li>' . get_the_title(get_option('page_for_posts')) . '</li>';
		} elseif (is_category()) {
			// Category archive
			$category = get_queried_object();
			echo '<li>' . $category->name . '</li>';
		} elseif (is_tag()) {
			// Tag archive
			$tag = get_queried_object();
			echo '<li>' . $tag->name . '</li>';
		} elseif (is_search()) {
			// Search results
			echo '<li>' . __("Výsledky vyhledávání", "jtkn") . ' „' . get_search_query() . '“</li>';
		} elseif (is_404()) {
			// 404 page
			echo '<li>' . __("Chyba 404", "jtkn") . '</li>';
		} elseif (is_archive()) {
			// Other archives
			$post_type = get_queried_object();
			echo '<li>' . $post_type->labels->name ?? "" . '</li>';
		}
	
		echo '</ul>';

		$output = ob_get_contents();
		ob_end_clean();

		return $output;
	}
}
