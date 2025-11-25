/**
 * Theme Options Admin JS
 */
(function($) {
    'use strict';
    
    const WebteroThemeOptions = {

        init: function() {
            this.initTabs();
            this.initColorPickers();
            this.initMediaUploader();
            this.initRepeaters();
            this.initRangeSliders();
            this.initVersionManager();
            this.initButtonGroups();
            this.initEnhancedSelects();
            this.initTipTapEditors();
            this.initCodeEditors();
            this.initUnsavedChangesWarning();
        },

        /**
         * Initialize tabs
         */
        initTabs: function() {
            const storageKey = 'webtero_active_tab_' + (webteroThemeOptions.pageId || 'default');

            // Restore last active tab from localStorage on page load
            const savedTab = localStorage.getItem(storageKey);
            if (savedTab) {
                // Check if the saved tab still exists
                const $savedTab = $('.nav-tab[data-tab="' + savedTab + '"]');
                if ($savedTab.length) {
                    // Activate the saved tab
                    $('.nav-tab').removeClass('nav-tab-active');
                    $savedTab.addClass('nav-tab-active');

                    $('.webtero-tab-content').hide();
                    $('.webtero-tab-content[data-tab-content="' + savedTab + '"]').show();

                    $('#webtero-current-tab').val(savedTab);
                }
            }

            // Handle tab clicks
            $(document).on('click', '.nav-tab', function(e) {
                e.preventDefault();

                const $tab = $(this);
                const tabId = $tab.data('tab');

                // Update active tab
                $('.nav-tab').removeClass('nav-tab-active');
                $tab.addClass('nav-tab-active');

                // Show corresponding content
                $('.webtero-tab-content').hide();
                $('.webtero-tab-content[data-tab-content="' + tabId + '"]').show();

                // Update hidden field to track current tab
                $('#webtero-current-tab').val(tabId);

                // Save to localStorage
                localStorage.setItem(storageKey, tabId);
            });
        },
        
        /**
         * Initialize color pickers
         */
        initColorPickers: function() {
            $('.webtero-color-picker').wpColorPicker();

            // Add close button to color picker popups
            setTimeout(() => {
                $('.wp-picker-holder').each(function() {
                    const $holder = $(this);

                    // Check if close button already exists
                    if ($holder.find('.webtero-color-close').length === 0) {
                        // Create close button
                        const $closeBtn = $('<button type="button" class="webtero-color-close" title="Close">&times;</button>');

                        // Prepend to picker holder
                        $holder.prepend($closeBtn);

                        // Add click handler
                        $closeBtn.on('click', function(e) {
                            e.preventDefault();
                            const $container = $(this).closest('.wp-picker-container');
                            const $toggle = $container.find('.wp-color-result');
                            $toggle.click(); // Toggle to close
                        });
                    }
                });
            }, 100);

            // Add close button when color picker is opened
            $(document).on('click', '.wp-color-result', function() {
                setTimeout(() => {
                    const $container = $(this).closest('.wp-picker-container');
                    const $holder = $container.find('.wp-picker-holder');

                    if ($holder.length && $holder.find('.webtero-color-close').length === 0) {
                        const $closeBtn = $('<button type="button" class="webtero-color-close" title="Close">&times;</button>');
                        $holder.prepend($closeBtn);

                        $closeBtn.on('click', function(e) {
                            e.preventDefault();
                            $(this).closest('.wp-picker-container').find('.wp-color-result').click();
                        });
                    }
                }, 10);
            });
        },
        
        /**
         * Initialize media uploader
         */
        initMediaUploader: function() {
            let mediaUploader;
            
            $(document).on('click', '.webtero-media-upload', function(e) {
                e.preventDefault();
                
                const $button = $(this);
                const $field = $button.closest('.webtero-media-field');
                const $input = $field.find('.webtero-media-id');
                const $preview = $field.find('.webtero-media-preview');
                const $remove = $field.find('.webtero-media-remove');
                
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                
                mediaUploader = wp.media({
                    title: webteroThemeOptions.strings.selectImage,
                    button: {
                        text: webteroThemeOptions.strings.useImage
                    },
                    multiple: false
                });
                
                mediaUploader.on('select', function() {
                    const attachment = mediaUploader.state().get('selection').first().toJSON();
                    $input.val(attachment.id);
                    $preview.html('<img src="' + attachment.url + '" alt="">');
                    $remove.show();
                });
                
                mediaUploader.open();
            });
            
            $(document).on('click', '.webtero-media-remove', function(e) {
                e.preventDefault();
                
                const $button = $(this);
                const $field = $button.closest('.webtero-media-field');
                const $input = $field.find('.webtero-media-id');
                const $preview = $field.find('.webtero-media-preview');
                
                $input.val('');
                $preview.html('');
                $button.hide();
            });
        },
        
        /**
         * Initialize repeaters
         */
        initRepeaters: function() {
            const self = this;
            
            // Add row
            $(document).on('click', '.webtero-repeater-add', function(e) {
                e.preventDefault();

                const $button = $(this);
                const $repeater = $button.closest('.webtero-repeater');
                const fieldId = $repeater.data('field-id');
                const $container = $repeater.find('.webtero-repeater-items');
                const template = $('#webtero-repeater-template-' + fieldId).html();
                const index = $container.children().length;

                const newRow = template.replace(/\{\{INDEX\}\}/g, index);
                $container.append(newRow);

                // Reinitialize color pickers in new row
                $container.find('.webtero-repeater-item:last-child .webtero-color-picker').wpColorPicker();

                // Initialize button groups in new row
                $container.find('.webtero-repeater-item:last-child .webtero-button-group input:checked').each(function() {
                    $(this).closest('.webtero-button-group-item').addClass('active');
                });
            });
            
            // Remove row
            $(document).on('click', '.webtero-repeater-remove', function(e) {
                e.preventDefault();
                
                if (confirm(webteroThemeOptions.strings.confirmDelete)) {
                    $(this).closest('.webtero-repeater-item').remove();
                }
            });
            
            // Make sortable
            $('.webtero-repeater-items').sortable({
                handle: '.webtero-repeater-handle',
                axis: 'y',
                opacity: 0.7,
                placeholder: 'webtero-repeater-placeholder',
                update: function(event, ui) {
                    self.updateRepeaterIndexes($(this));
                }
            });
        },
        
        /**
         * Update repeater field indexes after sorting
         */
        updateRepeaterIndexes: function($container) {
            $container.children('.webtero-repeater-item').each(function(index) {
                $(this).find('input, select, textarea').each(function() {
                    const $field = $(this);
                    const name = $field.attr('name');
                    
                    if (name) {
                        const newName = name.replace(/\[\d+\]/, '[' + index + ']');
                        $field.attr('name', newName);
                    }
                });
            });
        },
        
        /**
         * Initialize range sliders
         */
        initRangeSliders: function() {
            // Range slider -> Number input sync
            $(document).on('input', '.webtero-range-slider', function() {
                const $range = $(this);
                const $numberInput = $range.closest('.webtero-range-field-wrapper').find('.webtero-range-number');
                $numberInput.val($range.val());
            });

            // Number input -> Range slider sync (with validation)
            $(document).on('input change', '.webtero-range-number', function() {
                const $number = $(this);
                const $wrapper = $number.closest('.webtero-range-field-wrapper');
                const $range = $wrapper.find('.webtero-range-slider');

                let value = parseFloat($number.val());
                const min = parseFloat($number.attr('min'));
                const max = parseFloat($number.attr('max'));
                const step = parseFloat($number.attr('step')) || 1;

                // Validate and constrain value
                if (isNaN(value)) {
                    value = min;
                } else {
                    value = Math.max(min, Math.min(max, value));
                    // Snap to step
                    value = Math.round(value / step) * step;
                }

                // Update both inputs
                $number.val(value);
                $range.val(value);
            });

            // Initialize number inputs on page load
            $('.webtero-range-slider').each(function() {
                const $range = $(this);
                const $numberInput = $range.closest('.webtero-range-field-wrapper').find('.webtero-range-number');
                $numberInput.val($range.val());
            });
        },
        
        /**
         * Initialize version manager
         */
        initVersionManager: function() {
            // Restore version handler
            $(document).on('click', '.webtero-version-restore', function(e) {
                e.preventDefault();

                const version = $(this).data('version');
                const url = new URL(window.location.href);

                // Add action parameter
                url.searchParams.set('webtero_action', 'set_active_version');
                url.searchParams.set('version', version);
                url.searchParams.set('_wpnonce', webteroThemeOptions.nonce);

                window.location.href = url.toString();
            });

            // Delete version handler
            $(document).on('click', '.webtero-version-delete', function(e) {
                e.preventDefault();

                if (!confirm('Are you sure you want to delete this version? This action cannot be undone.')) {
                    return;
                }

                const version = $(this).data('version');
                const url = new URL(window.location.href);

                url.searchParams.set('webtero_action', 'delete_version');
                url.searchParams.set('version', version);
                url.searchParams.set('_wpnonce', webteroThemeOptions.nonce);

                window.location.href = url.toString();
            });
        },

        /**
         * Initialize button groups
         */
        initButtonGroups: function() {
            $(document).on('change', '.webtero-button-group input', function() {
                const $input = $(this);
                const $group = $input.closest('.webtero-button-group');
                const $item = $input.closest('.webtero-button-group-item');
                const isMultiple = $group.data('multiple') === 1;
                
                if (!isMultiple) {
                    // Radio behavior - deselect others
                    $group.find('.webtero-button-group-item').removeClass('active');
                }
                
                if ($input.is(':checked')) {
                    $item.addClass('active');
                } else {
                    $item.removeClass('active');
                }
            });
        },

        /**
         * Initialize enhanced selects (Choices.js)
         */
        initEnhancedSelects: function() {
            if (typeof Choices === 'undefined') {
                console.warn('Choices.js not loaded');
                return;
            }

            $('.webtero-enhanced-select').each(function() {
                const $select = $(this);
                const searchable = $select.data('searchable') !== 0;

                new Choices(this, {
                    searchEnabled: searchable,
                    itemSelectText: '',
                    shouldSort: false,
                    removeItemButton: $select.prop('multiple'),
                    classNames: {
                        containerOuter: 'choices webtero-choices'
                    }
                });
            });
        },

        /**
         * Initialize TipTap editors
         */
        initTipTapEditors: function() {
            if (typeof WebteroTipTap === 'undefined') {
                console.warn('WebteroTipTap not loaded');
                return;
            }

            // Set button classes from PHP
            if (webteroThemeOptions.buttonClasses) {
                WebteroTipTap.setButtonClasses(webteroThemeOptions.buttonClasses);
            }

            // Initialize all TipTap containers
            $('.webtero-tiptap-container').each(function() {
                const $container = $(this);
                const fieldName = $container.data('tiptap-field');
                const content = $container.data('tiptap-content') || '';

                WebteroTipTap.init(this, {
                    content: content,
                    fieldName: fieldName,
                    onChange: function(html) {
                        // Update hidden input for form submission
                        let $input = $container.find('input[name="webtero_options[' + fieldName + ']"]');
                        if (!$input.length) {
                            $input = $('<input type="hidden" name="webtero_options[' + fieldName + ']">');
                            $container.append($input);
                        }
                        $input.val(html);
                    }
                });
            });
        },

        /**
         * Initialize unsaved changes warning
         * Uses WordPress's built-in wp.data API for tracking form changes
         */
        initUnsavedChangesWarning: function() {
            const $form = $('#webtero-options-form');

            if (!$form.length) {
                return;
            }

            let hasUnsavedChanges = false;

            const markAsChanged = function() {
                hasUnsavedChanges = true;
            };

            // Track changes on all form fields (including dynamically added ones)
            $form.on('change input', 'input, select, textarea', markAsChanged);

            // Track color picker changes (WordPress color picker triggers change on hidden input)
            $form.on('change', '.wp-color-picker', markAsChanged);

            // Track WordPress color picker iris changes
            $(document).on('irischange', '.wp-color-picker', markAsChanged);

            // Track repeater add/remove actions
            $(document).on('click', '.webtero-repeater-add', markAsChanged);

            // Track repeater remove - the confirmation is already handled in initRepeaters
            // We just need to mark as changed when removal happens (after the row is actually removed)
            $(document).on('click', '.webtero-repeater-remove', function() {
                // Set a small timeout to mark as changed after the removal happens
                setTimeout(markAsChanged, 100);
            });

            // Track sortable changes in repeaters
            $('.webtero-repeater-items').on('sortupdate', markAsChanged);

            // Track media uploader changes
            $(document).on('click', '.webtero-media-upload, .webtero-media-remove', markAsChanged);

            // Track button group changes
            $(document).on('change', '.webtero-button-group input', markAsChanged);

            // Track enhanced select changes (Choices.js)
            $(document).on('change', '.webtero-enhanced-select', markAsChanged);

            // Clear flag when form is submitted
            $form.on('submit', function() {
                hasUnsavedChanges = false;
            });

            // WordPress-style beforeunload warning
            $(window).on('beforeunload', function(e) {
                if (hasUnsavedChanges) {
                    const message = 'The changes you made will be lost if you navigate away from this page.';
                    e.returnValue = message; // Standard browsers
                    return message; // Legacy browsers
                }
            });

            // Warn when clicking internal WordPress links
            $(document).on('click', 'a:not([target="_blank"])', function(e) {
                if (hasUnsavedChanges) {
                    // Allow form submission buttons
                    if ($(this).closest('form').is($form)) {
                        return;
                    }

                    // Don't warn for anchors on same page
                    const href = $(this).attr('href');
                    if (href && href.charAt(0) === '#') {
                        return;
                    }

                    const confirmLeave = confirm('The changes you made will be lost if you navigate away from this page.');
                    if (!confirmLeave) {
                        e.preventDefault();
                        e.stopPropagation();
                        return false;
                    }
                }
            });
        },

        /**
         * Initialize CodeMirror editors
         */
        initCodeEditors: function() {
            if (typeof CodeMirror === 'undefined') {
                return;
            }

            $('.webtero-code-editor').each(function() {
                const $textarea = $(this);
                const mode = $textarea.data('mode') || 'htmlmixed';

                // Initialize CodeMirror
                const editor = CodeMirror.fromTextArea(this, {
                    mode: mode,
                    theme: 'monokai',
                    lineNumbers: true,
                    lineWrapping: true,
                    indentUnit: 4,
                    indentWithTabs: true,
                    matchBrackets: true,
                    autoCloseBrackets: true,
                    styleActiveLine: true,
                    viewportMargin: Infinity,
                    extraKeys: {
                        "F11": function(cm) {
                            cm.setOption("fullScreen", !cm.getOption("fullScreen"));
                        },
                        "Esc": function(cm) {
                            if (cm.getOption("fullScreen")) cm.setOption("fullScreen", false);
                        },
                        "Tab": function(cm) {
                            cm.replaceSelection("\t");
                        }
                    }
                });

                // Store editor instance for later access
                $textarea.data('codemirror', editor);

                // Update textarea on editor change
                editor.on('change', function() {
                    editor.save();
                });

                // Refresh editor when it becomes visible (for tabs)
                $(document).on('click', '.nav-tab', function() {
                    setTimeout(function() {
                        editor.refresh();
                    }, 100);
                });
            });

            // Initialize CodeMirror for dynamically added repeater rows
            $(document).on('click', '.webtero-repeater-add', function() {
                setTimeout(function() {
                    $('.webtero-code-editor').each(function() {
                        if (!$(this).data('codemirror')) {
                            const mode = $(this).data('mode') || 'htmlmixed';
                            const editor = CodeMirror.fromTextArea(this, {
                                mode: mode,
                                theme: 'monokai',
                                lineNumbers: true,
                                lineWrapping: true,
                                indentUnit: 4,
                                indentWithTabs: true,
                                matchBrackets: true,
                                autoCloseBrackets: true,
                                styleActiveLine: true
                            });

                            $(this).data('codemirror', editor);

                            editor.on('change', function() {
                                editor.save();
                            });
                        }
                    });
                }, 100);
            });
        },
    };

    // Initialize on document ready
    $(document).ready(function() {
        WebteroThemeOptions.init();
    });

})(jQuery);