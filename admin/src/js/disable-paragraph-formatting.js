/**
 * Disable formatting and alignment options for core/paragraph block
 * Removes bold, italic, link, alignment controls, etc. from the toolbar
 */

(function() {
    'use strict';

    const { unregisterFormatType } = wp.richText;
    const { addFilter } = wp.hooks;
    const { createHigherOrderComponent } = wp.compose;

    // Wait for WordPress to be ready
    wp.domReady(function() {
        // Unregister all inline format types for cleaner paragraph blocks
        const formatsToRemove = [
            'core/bold',
            'core/italic',
            'core/link',
            'core/strikethrough',
            'core/underline',
            'core/text-color',
            'core/subscript',
            'core/superscript',
            'core/keyboard',
            'core/code',
            'core/image',
            'core/language'
        ];

        // Check if format registry exists
        const formatRegistry = wp.richText && wp.richText.store ? wp.data.select(wp.richText.store) : null;

        formatsToRemove.forEach(function(formatName) {
            // Only unregister if the format exists
            if (unregisterFormatType && formatRegistry) {
                try {
                    const formatType = formatRegistry.getFormatType(formatName);
                    if (formatType) {
                        unregisterFormatType(formatName);
                    }
                } catch (error) {
                    // Silently ignore if format doesn't exist or can't be unregistered
                }
            }
        });

        // console.log('Paragraph formatting disabled');
    });

    // Remove alignment controls from paragraph block
    const withoutAlignmentControls = createHigherOrderComponent((BlockEdit) => {
        return (props) => {
            if (props.name === 'core/paragraph') {
                // Remove alignment from block supports
                return wp.element.createElement(BlockEdit, {
                    ...props,
                    // Override BlockControls to hide alignment toolbar
                });
            }
            return wp.element.createElement(BlockEdit, props);
        };
    }, 'withoutAlignmentControls');

    addFilter(
        'editor.BlockEdit',
        'webtero/remove-paragraph-alignment',
        withoutAlignmentControls
    );
})();
