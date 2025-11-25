<?php
/**
 * Tester Block Render Template
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

<div class="webtero-tester-block">
	<div class="container">
		<h2>Tester Block - All Field Types</h2>

		<div class="tester-grid">
			<!-- TEXT FIELDS -->
			<div class="tester-section">
				<h3>Text Input Fields</h3>

				<div class="tester-field">
					<strong>Text Field:</strong>
					<span><?php echo esc_html( $block['text_field'] ?? '' ); ?></span>
				</div>

				<div class="tester-field">
					<strong>Number Field:</strong>
					<span><?php echo esc_html( $block['number_field'] ?? '0' ); ?></span>
				</div>

				<div class="tester-field">
					<strong>Range Field:</strong>
					<span><?php echo esc_html( $block['range_field'] ?? '0' ); ?></span>
				</div>

				<div class="tester-field">
					<strong>Textarea Field:</strong>
					<pre><?php echo esc_html( $block['textarea_field'] ?? '' ); ?></pre>
				</div>
			</div>

			<!-- SELECTION FIELDS -->
			<div class="tester-section">
				<h3>Selection Fields</h3>

				<div class="tester-field">
					<strong>Radio Field:</strong>
					<span><?php echo esc_html( $block['radio_field'] ?? '' ); ?></span>
				</div>

				<div class="tester-field">
					<strong>Checkbox Field:</strong>
					<span><?php echo ! empty( $block['checkbox_field'] ) ? '✓ Checked' : '✗ Unchecked'; ?></span>
				</div>

				<div class="tester-field">
					<strong>Toggle Field:</strong>
					<span><?php echo ! empty( $block['toggle_field'] ) ? '✓ Enabled' : '✗ Disabled'; ?></span>
				</div>

				<div class="tester-field">
					<strong>Button Group Field:</strong>
					<span><?php echo esc_html( $block['button_group_field'] ?? '' ); ?></span>
				</div>

				<div class="tester-field">
					<strong>Button Group Multiple:</strong>
					<span><?php
						$multiple = $block['button_group_multiple'] ?? [];
						echo esc_html( is_array( $multiple ) ? implode( ', ', $multiple ) : $multiple );
					?></span>
				</div>

				<div class="tester-field">
					<strong>Select Field:</strong>
					<span><?php echo esc_html( $block['select_field'] ?? '' ); ?></span>
				</div>

				<div class="tester-field">
					<strong>Enhanced Select:</strong>
					<span><?php echo esc_html( $block['enhanced_select_field'] ?? '' ); ?></span>
				</div>

				<div class="tester-field">
					<strong>Enhanced Select Multiple:</strong>
					<span><?php
						$multiple = $block['enhanced_select_multiple'] ?? [];
						echo esc_html( is_array( $multiple ) ? implode( ', ', $multiple ) : $multiple );
					?></span>
				</div>
			</div>

			<!-- MEDIA & COLOR -->
			<div class="tester-section">
				<h3>Media & Color Fields</h3>

				<div class="tester-field">
					<strong>Color Field:</strong>
					<span style="display: inline-block; width: 50px; height: 20px; background-color: <?php echo esc_attr( $block['color_field'] ?? '#000000' ); ?>; border: 1px solid #ddd;"></span>
					<code><?php echo esc_html( $block['color_field'] ?? '' ); ?></code>
				</div>

				<div class="tester-field">
					<strong>Media Field:</strong>
					<?php if ( ! empty( $block['media_field'] ) ) :
						$image_url = wp_get_attachment_url( intval( $block['media_field'] ) );
						if ( $image_url ) : ?>
							<img src="<?php echo esc_url( $image_url ); ?>" alt="" style="max-width: 200px; height: auto; display: block; margin-top: 8px;">
						<?php else : ?>
							<span>ID: <?php echo esc_html( $block['media_field'] ); ?> (Image not found)</span>
						<?php endif;
					else : ?>
						<span>No image selected</span>
					<?php endif; ?>
				</div>
			</div>

			<!-- RICH TEXT -->
			<div class="tester-section" style="grid-column: 1 / -1;">
				<h3>Rich Text Editor</h3>

				<div class="tester-field">
					<strong>TipTap Field:</strong>
					<div style="margin-top: 8px; padding: 16px; background: #f9f9f9; border-radius: 4px;">
						<?php echo wp_kses_post( $block['tiptap_field'] ?? '' ); ?>
					</div>
				</div>
			</div>

			<!-- REPEATER -->
			<div class="tester-section" style="grid-column: 1 / -1;">
				<h3>Repeater Field</h3>

				<?php if ( ! empty( $block['repeater_field'] ) && is_array( $block['repeater_field'] ) ) : ?>
					<?php foreach ( $block['repeater_field'] as $index => $row ) : ?>
						<div class="tester-repeater-item" style="padding: 16px; background: #f9f9f9; border-radius: 4px; margin-bottom: 12px;">
							<h4>Row <?php echo esc_html( $index + 1 ); ?></h4>
							<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
								<div><strong>Text:</strong> <?php echo esc_html( $row['rep_text'] ?? '' ); ?></div>
								<div><strong>Number:</strong> <?php echo esc_html( $row['rep_number'] ?? '' ); ?></div>
								<div><strong>Select:</strong> <?php echo esc_html( $row['rep_select'] ?? '' ); ?></div>
								<div>
									<strong>Color:</strong>
									<span style="display: inline-block; width: 30px; height: 15px; background-color: <?php echo esc_attr( $row['rep_color'] ?? '#000000' ); ?>; border: 1px solid #ddd;"></span>
								</div>
							</div>
							<div style="margin-top: 12px;">
								<strong>Textarea:</strong>
								<pre style="margin: 4px 0; font-size: 12px;"><?php echo esc_html( $row['rep_textarea'] ?? '' ); ?></pre>
							</div>
							<?php if ( ! empty( $row['rep_tiptap'] ) ) : ?>
								<div style="margin-top: 12px;">
									<strong>TipTap:</strong>
									<div style="margin-top: 4px; padding: 8px; background: white; border-radius: 3px;">
										<?php echo wp_kses_post( $row['rep_tiptap'] ); ?>
									</div>
								</div>
							<?php endif; ?>
							<?php if ( ! empty( $row['rep_media'] ) ) :
								$image_url = wp_get_attachment_url( intval( $row['rep_media'] ) );
								if ( $image_url ) : ?>
									<div style="margin-top: 12px;">
										<strong>Media:</strong>
										<img src="<?php echo esc_url( $image_url ); ?>" alt="" style="max-width: 150px; height: auto; display: block; margin-top: 4px;">
									</div>
								<?php endif;
							endif; ?>
						</div>
					<?php endforeach; ?>
				<?php else : ?>
					<p>No repeater items</p>
				<?php endif; ?>
			</div>

			<!-- DEBUG INFO -->
			<?php if ( $block['is_preview'] ?? false ) : ?>
				<div class="tester-section" style="grid-column: 1 / -1;">
					<h3>Debug Info</h3>
					<details style="background: #f0f0f0; padding: 16px; border-radius: 4px;">
						<summary style="cursor: pointer; font-weight: 600;">Show Raw Block Data</summary>
						<pre style="margin-top: 12px; font-size: 11px; overflow-x: auto;"><?php print_r( $block ); ?></pre>
					</details>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
