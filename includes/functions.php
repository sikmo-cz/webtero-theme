<?php

    declare(strict_types = 1);
   
    defined( 'ABSPATH' ) || exit;  // Prevent direct access

    /**
     * Theme autoloader for classes in /class/ folder
     * Supports both WT\ and WT\Blocks\ namespaces
     */
    function theme_autoloader( string $class_name ): bool
    {
        if( ! str_contains( $class_name, 'WT' ) ) {
            return false;
        }

        $parts = explode( '\\', $class_name );

        // WT\Blocks\Something_Block -> blocks/class-something-block.php
        if ( isset( $parts[1] ) && $parts[1] === 'Blocks' && isset( $parts[2] ) ) {
            $class_file = str_replace( '_', '-', strtolower( $parts[2] ) );
            $class_path = THEME_CLASSES_DIR . 'blocks/class-' . $class_file . '.php';

            if ( file_exists( $class_path ) ) {
                require_once $class_path;
                return true;
            }
        }

        // WT\Something -> class-something.php
        if ( isset( $parts[1] ) ) {
            $class_file = str_replace( '_', '-', strtolower( $parts[1] ) );
            $class_path = THEME_CLASSES_DIR . 'class-' . $class_file . '.php';

            if ( file_exists( $class_path ) ) {
                require_once $class_path;
                return true;
            }
        }

        return false;
    }

    /**
     * Get theme option value
     *
     * @param string $key Option key
     * @param string $instance Instance ID
     * @param int|null $version Version timestamp
     * @param mixed $default Default value
     * @return mixed
     */
    function webtero_get_option( string $key, string $instance = '', ?int $version = null, mixed $default = null ): mixed
    {
        if ( ! class_exists( 'WT\\Theme_Options' ) ) {
            return $default;
        }

        $value = WT\Theme_Options::get_option( $key, $instance, $version );

        return $value !== null ? $value : $default;
    }

    /**
     * Get block data from attributes
     *
     * @param array $attributes Block attributes
     * @return array Decoded block data
     */
    function webtero_get_block_data( array $attributes ): array
    {
        $options_json = $attributes['webteroOptions'] ?? '';

        if ( empty( $options_json ) ) {
            return [];
        }

        $data = json_decode( $options_json, true );

        if ( ! is_array( $data ) ) {
            return [];
        }

        return $data;
    }

    /**
     * Check if current block is in preview mode (WordPress admin editor)
     *
     * Usage in render.php:
     *   if ( wt_block_in_preview() ) {
     *       // Show preview-specific content
     *   }
     *
     * @return bool True if in editor preview
     */
    function wt_block_in_preview(): bool
    {
        global $block;

        return ! empty( $block['is_preview'] );
    }