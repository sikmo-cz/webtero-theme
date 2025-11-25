<?php
/**
 * Text Content Block Render Template
 *
 * Pure HTML rendering - no logic, only display
 * Available variables:
 * - $block array - Prepared block data from prepare_render_data()
 * - $attributes array - Raw block attributes
 *
 * @package webtero
 */

defined( 'ABSPATH' ) || exit;

?>

<div class="container">
    <?php if ( ! empty( $block['title'] ) ) : ?>
        <h2><?php echo esc_html( $block['title'] ); ?></h2>
    <?php endif; ?>

    <?php if ( ! empty( $block['content'] ) ) : ?>
        <div class="ptc">
            <?php echo wp_kses_post( $block['content'] ); ?>
        </div>
    <?php endif; ?>
</div>
