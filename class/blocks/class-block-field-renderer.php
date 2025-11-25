<?php
/**
 * Block Field Renderer
 *
 * Renders field types for block configuration modals.
 * Extracted and adapted from Theme_Options class.
 *
 * @package webtero
 */

declare(strict_types = 1);

namespace WT\Blocks;

defined( 'ABSPATH' ) || exit;

class Block_Field_Renderer {

    /**
     * Render block fields for modal
     *
     * @param array $fields Field configuration
     * @param array $data Existing block data
     * @return string Rendered HTML
     */
    public function render_fields( array $fields, array $data ): string {
        ob_start();

        foreach ( $fields as $field ) {
            $this->render_field( $field, $data );
        }

        return ob_get_clean();
    }

    /**
     * Render a single field
     *
     * @param array $field Field configuration
     * @param array $data Block data
     */
    private function render_field( array $field, array $data ): void {
        $type = $field['type'] ?? 'text';
        $id = $field['id'] ?? '';
        $label = $field['label'] ?? '';
        $description = $field['description'] ?? '';
        $default = $field['default'] ?? '';
        $value = $data[ $id ] ?? $default;

        // Special handling for repeater
        if ( in_array( $type, [ 'repeater' ] ) ) {
            $this->render_repeater_field( $field, $value );
            return;
        }

        // Render field wrapper
        echo '<div class="webtero-field">';

        if ( $label ) {
            echo '<label for="webtero-option-' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label>';
        }

        // Render field input based on type
        switch ( $type ) {
            case 'text':
                $this->render_text_field( $field, $value );
                break;
            case 'number':
                $this->render_number_field( $field, $value );
                break;
            case 'range':
                $this->render_range_field( $field, $value );
                break;
            case 'textarea':
                $this->render_textarea_field( $field, $value );
                break;
            case 'radio':
                $this->render_radio_field( $field, $value );
                break;
            case 'checkbox':
                $this->render_checkbox_field( $field, $value );
                break;
            case 'toggle':
                $this->render_toggle_field( $field, $value );
                break;
            case 'button_group':
                $this->render_button_group_field( $field, $value );
                break;
            case 'color':
                $this->render_color_field( $field, $value );
                break;
            case 'select':
                $this->render_select_field( $field, $value );
                break;
            case 'enhanced_select':
                $this->render_enhanced_select_field( $field, $value );
                break;
            case 'media':
                $this->render_media_field( $field, $value );
                break;
            case 'tiptap':
                $this->render_tiptap_field( $field, $value );
                break;
        }

        if ( $description ) {
            echo '<p class="description">' . esc_html( $description ) . '</p>';
        }

        echo '</div>';
    }

    /**
     * Render text field
     *
     * @param array $field Field configuration
     * @param mixed $value Field value
     */
    private function render_text_field( array $field, $value ): void {
        ?>
        <input
            type="text"
            id="webtero-option-<?php echo esc_attr( $field['id'] ); ?>"
            class="webtero-autosave <?php echo esc_attr( $field['class'] ?? '' ); ?>"
            data-option="<?php echo esc_attr( $field['id'] ); ?>"
            value="<?php echo esc_attr( $value ); ?>"
            placeholder="<?php echo esc_attr( $field['placeholder'] ?? '' ); ?>"
        />
        <?php
    }

    /**
     * Render number field
     *
     * @param array $field Field configuration
     * @param mixed $value Field value
     */
    private function render_number_field( array $field, $value ): void {
        ?>
        <input
            type="number"
            id="webtero-option-<?php echo esc_attr( $field['id'] ); ?>"
            class="webtero-autosave <?php echo esc_attr( $field['class'] ?? '' ); ?>"
            data-option="<?php echo esc_attr( $field['id'] ); ?>"
            value="<?php echo esc_attr( $value ); ?>"
            min="<?php echo esc_attr( $field['min'] ?? '' ); ?>"
            max="<?php echo esc_attr( $field['max'] ?? '' ); ?>"
            step="<?php echo esc_attr( $field['step'] ?? '1' ); ?>"
        />
        <?php
    }

    /**
     * Render range field
     *
     * @param array $field Field configuration
     * @param mixed $value Field value
     */
    private function render_range_field( array $field, $value ): void {
        ?>
        <input
            type="range"
            id="webtero-option-<?php echo esc_attr( $field['id'] ); ?>"
            class="webtero-autosave <?php echo esc_attr( $field['class'] ?? '' ); ?>"
            data-option="<?php echo esc_attr( $field['id'] ); ?>"
            value="<?php echo esc_attr( $value ); ?>"
            min="<?php echo esc_attr( $field['min'] ?? '0' ); ?>"
            max="<?php echo esc_attr( $field['max'] ?? '100' ); ?>"
            step="<?php echo esc_attr( $field['step'] ?? '1' ); ?>"
        />
        <span class="webtero-range-value"><?php echo esc_html( $value ); ?></span>
        <?php
    }

    /**
     * Render textarea field
     *
     * @param array $field Field configuration
     * @param mixed $value Field value
     */
    private function render_textarea_field( array $field, $value ): void {
        ?>
        <textarea
            id="webtero-option-<?php echo esc_attr( $field['id'] ); ?>"
            class="webtero-autosave <?php echo esc_attr( $field['class'] ?? '' ); ?>"
            data-option="<?php echo esc_attr( $field['id'] ); ?>"
            rows="<?php echo esc_attr( $field['rows'] ?? '5' ); ?>"
            placeholder="<?php echo esc_attr( $field['placeholder'] ?? '' ); ?>"
        ><?php echo esc_textarea( $value ); ?></textarea>
        <?php
    }

    /**
     * Render radio field
     *
     * @param array $field Field configuration
     * @param mixed $value Field value
     */
    private function render_radio_field( array $field, $value ): void {
        $options = $field['options'] ?? [];

        foreach ( $options as $option_value => $option_label ) {
            ?>
            <label style="display: block; margin-bottom: 8px;">
                <input
                    type="radio"
                    name="webtero-option-<?php echo esc_attr( $field['id'] ); ?>"
                    class="webtero-autosave"
                    data-option="<?php echo esc_attr( $field['id'] ); ?>"
                    value="<?php echo esc_attr( $option_value ); ?>"
                    <?php checked( $value, $option_value ); ?>
                />
                <?php echo esc_html( $option_label ); ?>
            </label>
            <?php
        }
    }

    /**
     * Render checkbox field
     *
     * @param array $field Field configuration
     * @param mixed $value Field value
     */
    private function render_checkbox_field( array $field, $value ): void {
        ?>
        <label>
            <input
                type="checkbox"
                id="webtero-option-<?php echo esc_attr( $field['id'] ); ?>"
                class="webtero-autosave"
                data-option="<?php echo esc_attr( $field['id'] ); ?>"
                value="1"
                <?php checked( $value, 1 ); ?>
            />
            <?php echo esc_html( $field['checkbox_label'] ?? '' ); ?>
        </label>
        <?php
    }

    /**
     * Render toggle field
     *
     * @param array $field Field configuration
     * @param mixed $value Field value
     */
    private function render_toggle_field( array $field, $value ): void {
        $checked = ! empty( $value );
        $label_on = $field['label_on'] ?? __( 'Yes', 'webtero' );
        $label_off = $field['label_off'] ?? __( 'No', 'webtero' );
        $max_length = max( mb_strlen( $label_on ), mb_strlen( $label_off ) );
        $toggle_width = max( 60, ( $max_length * 8 ) + 30 );
        ?>
        <div class="webtero-toggle-wrapper">
            <label class="webtero-toggle" style="--toggle-width: <?php echo $toggle_width; ?>px;">
                <input
                    type="checkbox"
                    id="webtero-option-<?php echo esc_attr( $field['id'] ); ?>"
                    class="webtero-autosave"
                    data-option="<?php echo esc_attr( $field['id'] ); ?>"
                    value="1"
                    <?php checked( $checked ); ?>
                />
                <span class="webtero-toggle-track">
                    <span class="webtero-toggle-handle"></span>
                    <span class="webtero-toggle-labels">
                        <span class="webtero-toggle-label-off"><?php echo esc_html( $label_off ); ?></span>
                        <span class="webtero-toggle-label-on"><?php echo esc_html( $label_on ); ?></span>
                    </span>
                </span>
            </label>
        </div>
        <?php
    }

    /**
     * Render button group field
     *
     * @param array $field Field configuration
     * @param mixed $value Field value
     */
    private function render_button_group_field( array $field, $value ): void {
        $options = $field['options'] ?? [];
        $multiple = $field['multiple'] ?? false;
        $values = $multiple && is_array( $value ) ? $value : [ $value ];
        ?>
        <div class="webtero-button-group" data-multiple="<?php echo $multiple ? '1' : '0'; ?>">
            <?php foreach ( $options as $option_value => $option_config ) :
                $option_label = is_array( $option_config ) ? ( $option_config['label'] ?? $option_value ) : $option_config;
                $option_icon = is_array( $option_config ) ? ( $option_config['icon'] ?? '' ) : '';
                $is_checked = in_array( $option_value, $values );
                $input_type = $multiple ? 'checkbox' : 'radio';
            ?>
                <label class="webtero-button-group-item <?php echo $is_checked ? 'active' : ''; ?>">
                    <input
                        type="<?php echo $input_type; ?>"
                        class="webtero-autosave"
                        data-option="<?php echo esc_attr( $field['id'] ); ?>"
                        value="<?php echo esc_attr( $option_value ); ?>"
                        <?php checked( $is_checked ); ?>
                    />
                    <?php if ( $option_icon ) : ?>
                        <span class="webtero-button-icon"><?php echo $option_icon; ?></span>
                    <?php endif; ?>
                    <span class="webtero-button-text"><?php echo esc_html( $option_label ); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Render color field
     *
     * @param array $field Field configuration
     * @param mixed $value Field value
     */
    private function render_color_field( array $field, $value ): void {
        ?>
        <input
            type="text"
            id="webtero-option-<?php echo esc_attr( $field['id'] ); ?>"
            class="webtero-color-picker webtero-autosave"
            data-option="<?php echo esc_attr( $field['id'] ); ?>"
            value="<?php echo esc_attr( $value ); ?>"
        />
        <?php
    }

    /**
     * Render select field
     *
     * @param array $field Field configuration
     * @param mixed $value Field value
     */
    private function render_select_field( array $field, $value ): void {
        $options = $field['options'] ?? [];
        ?>
        <select
            id="webtero-option-<?php echo esc_attr( $field['id'] ); ?>"
            class="webtero-autosave"
            data-option="<?php echo esc_attr( $field['id'] ); ?>"
        >
            <?php foreach ( $options as $option_value => $option_label ) : ?>
                <option value="<?php echo esc_attr( $option_value ); ?>" <?php selected( $value, $option_value ); ?>>
                    <?php echo esc_html( $option_label ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Render enhanced select field
     *
     * @param array $field Field configuration
     * @param mixed $value Field value
     */
    private function render_enhanced_select_field( array $field, $value ): void {
        $options = $field['options'] ?? [];
        $multiple = $field['multiple'] ?? false;
        $searchable = $field['searchable'] ?? true;
        $values = $multiple && is_array( $value ) ? $value : [ $value ];
        ?>
        <select
            id="webtero-option-<?php echo esc_attr( $field['id'] ); ?>"
            class="webtero-enhanced-select webtero-autosave"
            data-option="<?php echo esc_attr( $field['id'] ); ?>"
            <?php echo $multiple ? 'multiple' : ''; ?>
            data-placeholder="<?php echo esc_attr( $field['placeholder'] ?? __( 'Select...', 'webtero' ) ); ?>"
            data-searchable="<?php echo $searchable ? '1' : '0'; ?>"
        >
            <?php if ( ! $multiple ) : ?>
                <option value=""><?php echo esc_html( $field['placeholder'] ?? __( '— Select —', 'webtero' ) ); ?></option>
            <?php endif; ?>

            <?php foreach ( $options as $option_value => $option_label ) : ?>
                <option
                    value="<?php echo esc_attr( $option_value ); ?>"
                    <?php echo in_array( $option_value, $values ) ? 'selected' : ''; ?>
                >
                    <?php echo esc_html( $option_label ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Render media field
     *
     * @param array $field Field configuration
     * @param mixed $value Field value
     */
    private function render_media_field( array $field, $value ): void {
        $image_url = $value ? wp_get_attachment_url( intval( $value ) ) : '';
        ?>
        <div class="webtero-media-field">
            <input
                type="hidden"
                id="webtero-option-<?php echo esc_attr( $field['id'] ); ?>"
                class="webtero-media-id webtero-autosave"
                data-option="<?php echo esc_attr( $field['id'] ); ?>"
                value="<?php echo esc_attr( $value ); ?>"
            />
            <div class="webtero-media-preview">
                <?php if ( $image_url ) : ?>
                    <img src="<?php echo esc_url( $image_url ); ?>" alt="">
                <?php endif; ?>
            </div>
            <button type="button" class="button webtero-media-upload">
                <?php _e( 'Select Image', 'webtero' ); ?>
            </button>
            <button type="button" class="button webtero-media-remove" <?php echo $value ? '' : 'style="display:none;"'; ?>>
                <?php _e( 'Remove', 'webtero' ); ?>
            </button>
        </div>
        <?php
    }

    /**
     * Render TipTap field
     *
     * @param array $field Field configuration
     * @param mixed $value Field value
     */
    private function render_tiptap_field( array $field, $value ): void {
        ?>
        <div
            id="tiptap-editor-<?php echo esc_attr( $field['id'] ); ?>"
            class="webtero-tiptap-container"
            data-tiptap-field="<?php echo esc_attr( $field['id'] ); ?>"
            data-tiptap-content="<?php echo esc_attr( $value ); ?>"
        ></div>
        <?php
    }

    /**
     * Render repeater field
     *
     * @param array $field Field configuration
     * @param mixed $value Field value
     */
    private function render_repeater_field( array $field, $value ): void {
        $id = $field['id'];
        $label = $field['label'] ?? '';
        $sub_fields = $field['fields'] ?? [];
        $values = is_array( $value ) ? $value : [];
        $min = $field['min'] ?? 0;
        $max = $field['max'] ?? 999;

        echo '<div class="webtero-field webtero-repeater-field">';

        if ( $label ) {
            echo '<label><strong>' . esc_html( $label ) . '</strong></label>';
        }

        echo '<div class="webtero-repeater" data-field-id="' . esc_attr( $id ) . '">';
        echo '<div class="webtero-repeater-items">';

        if ( ! empty( $values ) ) {
            foreach ( $values as $index => $row_data ) {
                $this->render_repeater_row( $id, $sub_fields, $row_data, $index );
            }
        }

        echo '</div>';

        $disabled = count( $values ) >= $max ? ' disabled' : '';
        echo '<button type="button" class="button webtero-repeater-add"' . $disabled . '>';
        echo __( 'Add Row', 'webtero' );
        if ( $max < 999 ) {
            echo ' <span class="webtero-repeater-count">(' . count( $values ) . '/' . $max . ')</span>';
        }
        echo '</button>';

        if ( $min > 0 ) {
            echo '<p class="description">' . sprintf( __( 'Minimum %d items required', 'webtero' ), $min ) . '</p>';
        }

        echo '</div>';

        // Template for new rows
        echo '<script type="text/html" id="webtero-repeater-template-' . esc_attr( $id ) . '">';
        $this->render_repeater_row( $id, $sub_fields, [], '{{INDEX}}' );
        echo '</script>';

        echo '</div>';
    }

    /**
     * Render repeater row
     *
     * @param string $parent_id Parent field ID
     * @param array $fields Sub-fields configuration
     * @param array $data Row data
     * @param int|string $index Row index
     */
    private function render_repeater_row( string $parent_id, array $fields, array $data, $index ): void {
        ?>
        <div class="webtero-repeater-item" data-index="<?php echo esc_attr( $index ); ?>">
            <div class="webtero-repeater-handle">
                <span class="dashicons dashicons-menu"></span>
            </div>
            <div class="webtero-repeater-content">
                <?php foreach ( $fields as $field ) :
                    $field_id = $field['id'];
                    $field_value = $data[ $field_id ] ?? ( $field['default'] ?? '' );
                    $field_data_attr = "{$parent_id}[{$index}][{$field_id}]";
                ?>
                    <div class="webtero-repeater-field">
                        <label><?php echo esc_html( $field['label'] ?? $field_id ); ?></label>
                        <?php $this->render_repeater_field_input( $field, $field_data_attr, $field_value ); ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="webtero-repeater-actions">
                <button type="button" class="button webtero-repeater-remove">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </div>
        </div>
        <?php
    }

    /**
     * Render repeater field input
     *
     * @param array $field Field configuration
     * @param string $data_option Data option attribute value
     * @param mixed $value Field value
     */
    private function render_repeater_field_input( array $field, string $data_option, $value ): void {
        $type = $field['type'] ?? 'text';

        switch ( $type ) {
            case 'text':
                echo '<input type="text" class="webtero-autosave" data-option="' . esc_attr( $data_option ) . '" value="' . esc_attr( $value ) . '">';
                break;
            case 'number':
                echo '<input type="number" class="webtero-autosave" data-option="' . esc_attr( $data_option ) . '" value="' . esc_attr( $value ) . '">';
                break;
            case 'textarea':
                echo '<textarea class="webtero-autosave" data-option="' . esc_attr( $data_option ) . '" rows="3">' . esc_textarea( $value ) . '</textarea>';
                break;
            case 'color':
                echo '<input type="text" class="webtero-color-picker webtero-autosave" data-option="' . esc_attr( $data_option ) . '" value="' . esc_attr( $value ) . '">';
                break;
            case 'select':
                echo '<select class="webtero-autosave" data-option="' . esc_attr( $data_option ) . '">';
                foreach ( $field['options'] ?? [] as $opt_val => $opt_label ) {
                    echo '<option value="' . esc_attr( $opt_val ) . '" ' . selected( $value, $opt_val, false ) . '>' . esc_html( $opt_label ) . '</option>';
                }
                echo '</select>';
                break;
            case 'media':
                $image_url = $value ? wp_get_attachment_url( intval( $value ) ) : '';
                echo '<div class="webtero-media-field">';
                echo '<input type="hidden" class="webtero-media-id webtero-autosave" data-option="' . esc_attr( $data_option ) . '" value="' . esc_attr( $value ) . '">';
                echo '<div class="webtero-media-preview">';
                if ( $image_url ) {
                    echo '<img src="' . esc_url( $image_url ) . '" alt="">';
                }
                echo '</div>';
                echo '<button type="button" class="button webtero-media-upload">' . __( 'Select', 'webtero' ) . '</button>';
                echo '<button type="button" class="button webtero-media-remove"' . ( $value ? '' : ' style="display:none;"' ) . '>' . __( 'Remove', 'webtero' ) . '</button>';
                echo '</div>';
                break;
            case 'tiptap':
                echo '<div class="webtero-tiptap-container" data-tiptap-field="' . esc_attr( $data_option ) . '" data-tiptap-content="' . esc_attr( $value ) . '"></div>';
                break;
        }
    }
}
