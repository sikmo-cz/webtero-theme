<?php
/**
 * Columns Block Render Template
 *
 * Displays all field values for testing purposes
 * Available variables:
 * - $block array - Prepared block data from prepare_render_data()
 * - $attributes array - Raw block attributes
 *
 * @package webtero
 */

defined( 'ABSPATH' ) || exit;

?>

<div class="container">
	<?php print_r( $block ); ?>
	<?php // print_r( $attributes ); ?>
</div>
