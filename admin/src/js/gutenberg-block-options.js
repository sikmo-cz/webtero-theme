(function() {
    'use strict';

    const { createElement: el } = wp.element;
    const { addFilter } = wp.hooks;
    const { InspectorControls, BlockControls } = wp.blockEditor;
    const { PanelBody, TextControl, Button, ToolbarGroup, ToolbarButton, Dropdown, MenuGroup, MenuItem } = wp.components;
    const { Fragment } = wp.element;
    const { createHigherOrderComponent } = wp.compose;
    const { select, dispatch } = wp.data;
    const { __ } = wp.i18n;
    const apiFetch = wp.apiFetch;

    // Custom Webtero logo icon
    const webteroIcon = el('svg', {
        width: 24,
        height: 24,
        viewBox: '0 0 84 62',
        fill: 'currentColor',
        xmlns: 'http://www.w3.org/2000/svg'
    }, el('path', {
        d: 'M9.67645 61.0269L0 0H12.612L20.114 50.1292H25.0066L34.5743 5.66678H49.1433L58.711 50.1292H63.6036L71.1056 0H83.7176L74.0411 61.0269H50.8829L41.8588 19.1799L32.8347 61.0269H9.67645Z'
    }));
    
    // Modal element reference
    let modal = null;
    let modalContent = null;
    let currentBlockId = null;
    let autoSaveTimeout = null;
    
    /**
     * Create modal element on page load
     */
    function createModal() {
        if (modal) return;
        
        // Create modal container
        modal = document.createElement('div');
        modal.id = 'webtero-block-options-modal';
        modal.className = 'webtero-modal';
        modal.style.display = 'none';
        
        // Create modal content wrapper
        modalContent = document.createElement('div');
        modalContent.className = 'webtero-modal-content';
        
        // Create close button
        const closeBtn = document.createElement('button');
        closeBtn.className = 'webtero-modal-close';
        closeBtn.innerHTML = '×';
        closeBtn.onclick = hideModal;
        
        // Create modal header
        const header = document.createElement('div');
        header.className = 'webtero-modal-header';
        header.innerHTML = '<h3>Block Options</h3>';
        
        // Add auto-save indicator
        const autoSaveIndicator = document.createElement('span');
        autoSaveIndicator.id = 'webtero-autosave-indicator';
        autoSaveIndicator.className = 'webtero-autosave-indicator';
        autoSaveIndicator.style.display = 'none';
        autoSaveIndicator.innerHTML = '💾 Saving...';
        header.appendChild(autoSaveIndicator);
        
        header.appendChild(closeBtn);
        
        // Create modal body with loading state
        const body = document.createElement('div');
        body.className = 'webtero-modal-body';
        body.id = 'webtero-modal-body';
        body.innerHTML = '<div class="webtero-loading">Loading options...</div>';
        
        modalContent.appendChild(header);
        modalContent.appendChild(body);
        modal.appendChild(modalContent);
        document.body.appendChild(modal);
        
        // Close modal when clicking outside
        modal.onclick = function(e) {
            if (e.target === modal) {
                hideModal();
            }
        };
    }
    
    /**
     * Show modal with current block options
     * Fetches form HTML from PHP endpoint
     *
     * @param {string} blockId Block client ID
     */
    async function showModal(blockId) {
        if (!modal) createModal();

        currentBlockId = blockId;
        const block = select('core/block-editor').getBlock(blockId);

        if (!block) return;

        // Get block name (e.g., "wt/text-content" or "core/paragraph")
        const blockName = block.name;

        // Determine which attribute to use for settings
        // New blocks use webteroSettings (object), old system uses webteroOptions (JSON string)
        const useSettingsAttr = block.attributes.hasOwnProperty('webteroSettings');
        const settingsAttrName = useSettingsAttr ? 'webteroSettings' : 'webteroOptions';

        // Get existing options from attribute
        let options = {};
        if (useSettingsAttr) {
            // webteroSettings is an object
            options = block.attributes.webteroSettings || {};
        } else if (block.attributes.webteroOptions) {
            // webteroOptions is a JSON string (old system)
            try {
                options = JSON.parse(block.attributes.webteroOptions);
            } catch (e) {
                options = {};
            }
        }

        // Show modal with loading state
        modal.style.display = 'flex';
        const body = document.getElementById('webtero-modal-body');
        body.innerHTML = '<div class="webtero-loading">Loading options...</div>';

        // Determine which endpoint to use based on block type
        let apiPath;
        if (blockName.startsWith('wt/')) {
            // Custom Webtero block - use block-specific fields endpoint
            const encodedBlockName = encodeURIComponent(blockName);
            apiPath = `/webtero/v1/block-fields/${encodedBlockName}?options=${encodeURIComponent(JSON.stringify(options))}`;
        } else {
            // Core or other blocks - use generic form
            apiPath = webteroBlockOptions.restUrl + '?options=' + encodeURIComponent(JSON.stringify(options));
        }

        // Fetch form HTML from PHP
        try {
            const response = await apiFetch({
                path: apiPath,
                method: 'GET'
            });

            if (response.success && response.html) {
                body.innerHTML = response.html;

                // Initialize TipTap editors
                initializeTipTapEditors();

                // Setup auto-save listeners on all form fields
                setupAutoSave();
            } else {
                body.innerHTML = '<div class="webtero-error">Failed to load form options.</div>';
            }
        } catch (error) {
            console.error('Error loading form:', error);
            body.innerHTML = '<div class="webtero-error">Error loading form. Please try again.</div>';
        }
    }
    
    /**
     * Destroy all TipTap editors in the modal
     */
    function destroyTipTapEditors() {
        if (!window.WebteroTipTap) return;

        // Find all TipTap containers in the modal
        const modalBody = document.getElementById('webtero-modal-body');
        if (!modalBody) return;

        const tiptapContainers = modalBody.querySelectorAll('.webtero-tiptap-container');

        tiptapContainers.forEach(container => {
            const instanceId = container.dataset.tiptapInstance;
            if (instanceId) {
                // Use the new destroyInstance method which handles cleanup properly
                window.WebteroTipTap.destroyInstance(instanceId);
                // console.log('Destroyed TipTap instance:', instanceId);
            }
        });
    }

    /**
     * Initialize all TipTap editors in the modal
     */
    function initializeTipTapEditors() {
        // Destroy any existing instances first to prevent conflicts
        destroyTipTapEditors();

        // Find all TipTap containers
        const tiptapContainers = document.querySelectorAll('.webtero-tiptap-container');

        tiptapContainers.forEach(container => {
            const fieldName = container.getAttribute('data-tiptap-field');
            const content = container.getAttribute('data-tiptap-content') || '';

            // Initialize TipTap with our general implementation
            if (window.WebteroTipTap) {
                window.WebteroTipTap.init(container, {
                    content: content,
                    fieldName: fieldName,
                    autosave: true  // Enable autosave for modal fields
                });
            } else {
                console.error('WebteroTipTap not loaded!');
            }
        });
    }
    
    /**
     * Setup auto-save listeners on all form fields with class "webtero-autosave"
     */
    function setupAutoSave() {
        const formFields = document.querySelectorAll('.webtero-autosave');
        
        formFields.forEach(field => {
            // Handle different event types based on field type
            const eventType = field.tagName === 'SELECT' || field.type === 'color' ? 'change' : 'input';
            
            field.addEventListener(eventType, function() {
                // Clear existing timeout
                if (autoSaveTimeout) {
                    clearTimeout(autoSaveTimeout);
                }
                
                // Show saving indicator
                showSavingIndicator();
                
                // Debounce: wait 500ms after user stops typing
                autoSaveTimeout = setTimeout(() => {
                    autoSaveBlockOptions();
                }, 500);
            });
        });
    }
    
    /**
     * Show saving indicator in modal header
     */
    function showSavingIndicator() {
        const indicator = document.getElementById('webtero-autosave-indicator');
        if (indicator) {
            indicator.style.display = 'inline-block';
            indicator.innerHTML = '💾 Saving...';
            indicator.className = 'webtero-autosave-indicator saving';
        }
    }
    
    /**
     * Show saved indicator
     */
    function showSavedIndicator() {
        const indicator = document.getElementById('webtero-autosave-indicator');
        if (indicator) {
            indicator.innerHTML = '✓ Saved';
            indicator.className = 'webtero-autosave-indicator saved';
            
            // Hide after 2 seconds
            setTimeout(() => {
                indicator.style.display = 'none';
            }, 2000);
        }
    }
    
    /**
     * Auto-save block options from all form fields
     */
    function autoSaveBlockOptions() {
        if (!currentBlockId) return;

        // Get current block to determine which attribute to use
        const block = select('core/block-editor').getBlock(currentBlockId);
        if (!block) return;

        // Determine which attribute to use
        const useSettingsAttr = block.attributes.hasOwnProperty('webteroSettings');
        const settingsAttrName = useSettingsAttr ? 'webteroSettings' : 'webteroOptions';

        // Collect all form data from fields with class "webtero-autosave"
        // Look specifically in the modal body to catch all fields including TipTap hidden inputs
        const modalBody = document.getElementById('webtero-modal-body');
        if (!modalBody) {
            console.error('Modal body not found');
            return;
        }

        const formFields = modalBody.querySelectorAll('.webtero-autosave');
        const options = {};

        // console.log('AutoSave: Found', formFields.length, 'fields');

        formFields.forEach(field => {
            const optionName = field.getAttribute('data-option');
            if (optionName) {
                options[optionName] = field.value;
                // console.log('AutoSave field:', optionName, '=', field.value.substring(0, 50) + (field.value.length > 50 ? '...' : ''));
            }
        });

        // Add timestamp
        options.timestamp = new Date().toISOString();

        // console.log('AutoSave: Saving options to', settingsAttrName, options);

        // Save to appropriate attribute
        if (useSettingsAttr) {
            // Save as object to webteroSettings
            dispatch('core/block-editor').updateBlockAttributes(currentBlockId, {
                webteroSettings: options
            });
        } else {
            // Save as JSON string to webteroOptions (old system)
            dispatch('core/block-editor').updateBlockAttributes(currentBlockId, {
                webteroOptions: JSON.stringify(options)
            });
        }

        // Show saved indicator
        showSavedIndicator();
    }
    
    // Expose autosave globally so TipTap can trigger it
    window.webteroAutoSave = autoSaveBlockOptions;

    // Expose modal function globally so blocks can open it
    window.webteroOpenBlockModal = showModal;
    
    /**
     * Hide modal
     */
    function hideModal() {
        if (modal) {
            // Destroy TipTap instances before hiding to prevent memory leaks
            destroyTipTapEditors();

            modal.style.display = 'none';
            currentBlockId = null;
        }
    }
    
    /**
     * Add custom attribute to all blocks for storing options
     */
    function addCustomAttribute(settings) {
        // Add our custom attribute to store JSON options
        settings.attributes = Object.assign(settings.attributes, {
            webteroOptions: {
                type: 'string',
                default: ''
            }
        });
        
        return settings;
    }
    
    wp.hooks.addFilter(
        'blocks.registerBlockType',
        'webtero/add-custom-attribute',
        addCustomAttribute
    );
    
    /**
     * Add custom controls to block toolbar
     * This adds the settings icon (cogwheel) in the block toolbar options (3 dots menu)
     */
    const withCustomControls = createHigherOrderComponent((BlockEdit) => {
        return (props) => {
            const { attributes, clientId, isSelected, name } = props;

            // Skip Block Options for core/paragraph
            if (name === 'core/paragraph') {
                return el(BlockEdit, props);
            }

            // Only show when block is selected
            if (!isSelected) {
                return el(BlockEdit, props);
            }
            
            return el(
                Fragment,
                {},
                el(BlockEdit, props),
                // Add button to toolbar (appears in 3-dot menu area)
                el(
                    BlockControls,
                    { group: 'other' },
                    el(
                        ToolbarButton,
                        {
                            icon: webteroIcon,
                            label: 'Block Options',
                            onClick: () => showModal(clientId),
                            className: 'webtero-toolbar-button'
                        }
                    )
                ),
                // Keep the sidebar panel too
                el(
                    InspectorControls,
                    {},
                    el(
                        PanelBody,
                        {
                            title: 'Block Options',
                            initialOpen: false
                        },
                        el(
                            'div',
                            { style: { padding: '10px 0' } },
                            el(
                                Button,
                                {
                                    variant: 'secondary',
                                    onClick: () => showModal(clientId),
                                    icon: webteroIcon,
                                    style: { width: '100%' }
                                },
                                'Open Block Options'
                            ),
                            el(
                                'p',
                                { 
                                    style: { 
                                        fontSize: '11px', 
                                        color: '#666',
                                        marginTop: '10px',
                                        marginBottom: 0
                                    } 
                                },
                                attributes.webteroOptions 
                                    ? '✓ Custom options saved' 
                                    : 'No custom options set'
                            )
                        )
                    )
                )
            );
        };
    }, 'withCustomControls');
    
    wp.hooks.addFilter(
        'editor.BlockEdit',
        'webtero/with-custom-controls',
        withCustomControls
    );
    
    /**
     * Initialize modal when editor is ready
     */
    wp.domReady(function() {
        createModal();
        
        if (typeof webteroBlockOptions !== 'undefined' && webteroBlockOptions.buttonClasses && window.WebteroTipTap) {
            window.WebteroTipTap.setButtonClasses(webteroBlockOptions.buttonClasses);
        }
    });
    
})();