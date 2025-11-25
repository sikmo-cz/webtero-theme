/**
 * Universal Block Editor
 *
 * Single editor that works for ALL Webtero blocks
 * Fields are defined in PHP and fetched via REST API
 * No need to write React - PHP does everything!
 *
 * @package webtero
 */

(function() {
	'use strict';

	const { useBlockProps, BlockControls } = wp.blockEditor;
	const {
		TextControl,
		TextareaControl,
		Spinner,
		ToolbarGroup,
		ToolbarButton,
		RangeControl,
		RadioControl,
		CheckboxControl,
		ToggleControl,
		Button,
		SelectControl,
		Dropdown
	} = wp.components;
	const { createElement: el, useState, useEffect, Fragment, useCallback, useRef } = wp.element;
	const { useSelect } = wp.data;
	const { MediaUpload } = wp.blockEditor;
	const ServerSideRender = wp.serverSideRender;

	/**
	 * TipTap Field Component with proper cleanup
	 * Defined outside to prevent re-renders and ensure proper initialization/cleanup
	 */
	const TipTapField = ({ fieldId, value, label, help, setAttributesFn }) => {
		const containerRef = useRef(null);
		const editorInstanceRef = useRef(null);
		const setAttributesFnRef = useRef(setAttributesFn);

		// Keep setAttributesFn ref updated so onChange always uses the latest function
		useEffect(() => {
			setAttributesFnRef.current = setAttributesFn;
		}, [setAttributesFn]);

		useEffect(() => {
			if (containerRef.current && window.WebteroTipTap) {
				// Initialize TipTap
				const instance = window.WebteroTipTap.init(containerRef.current, {
					content: value,
					autosave: false,
					onChange: (html) => {
						// Use ref to always get the latest setAttributesFn (prevents stale closures)
						setAttributesFnRef.current({ [fieldId]: html });
					}
				});

				editorInstanceRef.current = instance;

				// Cleanup function - destroy TipTap when component unmounts
				return () => {
					if (editorInstanceRef.current && editorInstanceRef.current.destroy) {
						editorInstanceRef.current.destroy();
					}
				};
			}
		}, []); // Empty deps - only initialize once per component instance

		return el('div', {
			style: { marginBottom: '20px' }
		},
			label && el('label', {
				style: {
					display: 'block',
					marginBottom: '8px',
					fontSize: '11px',
					fontWeight: 600,
					textTransform: 'uppercase',
					color: '#1e1e1e'
				}
			}, label),
			el('div', {
				ref: containerRef,
				className: 'webtero-tiptap-container',
				'data-tiptap-field': fieldId,
				'data-tiptap-content': value,
				style: {
					minHeight: '200px',
					border: '1px solid #ddd',
					borderRadius: '4px',
					padding: '10px',
					backgroundColor: '#fff'
				}
			}),
			help && el('p', {
				style: {
					marginTop: '8px',
					fontSize: '12px',
					fontStyle: 'normal',
					color: '#757575'
				}
			}, help)
		);
	};

	/**
	 * Media Field Component (defined outside to prevent re-renders)
	 */
	const MediaField = ({ field, value, setAttributes, fieldId }) => {
		const mediaId = value ? parseInt(value) : 0;
		const [imageUrl, setImageUrl] = useState('');

		// Fetch image URL when mediaId changes
		useEffect(() => {
			if (mediaId) {
				wp.apiFetch({ path: `/wp/v2/media/${mediaId}` })
					.then(media => {
						if (media && media.source_url) {
							setImageUrl(media.source_url);
						}
					})
					.catch(() => {
						setImageUrl('');
					});
			} else {
				setImageUrl('');
			}
		}, [mediaId]);

		return el('div', {
			style: { marginBottom: '20px' }
		},
			el('label', {
				style: {
					display: 'block',
					marginBottom: '8px',
					fontSize: '11px',
					fontWeight: 600,
					textTransform: 'uppercase'
				}
			}, field.label),
			el(MediaUpload, {
				onSelect: (media) => {
					setAttributes({ [fieldId]: media.id.toString() });
				},
				allowedTypes: ['image'],
				value: mediaId,
				render: ({ open }) => {
					return el('div', {},
						// Image preview
						imageUrl && el('div', {
							style: {
								marginBottom: '10px',
								border: '1px solid #ddd',
								borderRadius: '4px',
								padding: '4px',
								backgroundColor: '#f9f9f9',
								display: 'inline-block'
							}
						},
							el('img', {
								src: imageUrl,
								style: {
									maxWidth: '200px',
									height: 'auto',
									display: 'block'
								}
							}),
							el('span', {
								style: {
									fontSize: '11px',
									color: '#666',
									marginTop: '4px',
									display: 'block'
								}
							}, `ID: ${mediaId}`)
						),
						// Buttons
						el('div', { style: { marginTop: imageUrl ? '10px' : '0' } },
							el(Button, {
								onClick: open,
								variant: mediaId ? 'secondary' : 'primary'
							}, mediaId ? 'Change Image' : 'Select Image'),
							mediaId && el(Button, {
								onClick: () => setAttributes({ [fieldId]: '' }),
								isDestructive: true,
								variant: 'secondary',
								style: { marginLeft: '10px' }
							}, 'Remove')
						)
					);
				}
			}),
			field.description && el('p', {
				style: {
					marginTop: '8px',
					fontSize: '12px',
					color: '#757575'
				}
			}, field.description)
		);
	};

	/**
	 * File Field Component
	 * Generic file picker with type filtering
	 */
	const FileField = ({ field, value, setAttributes, fieldId }) => {
		const { MediaUpload } = wp.blockEditor || wp.editor;
		const { Button } = wp.components;
		const fileId = value ? parseInt(value, 10) : 0;
		const [fileData, setFileData] = useState(null);

		// Get allowed types from field config (e.g., ['video/mp4', 'video'] or specific mime types)
		const allowedTypes = field.allowed_types || [];

		// Fetch file data when ID changes
		useEffect(() => {
			if (!fileId) {
				setFileData(null);
				return;
			}

			wp.apiFetch({ path: `/wp/v2/media/${fileId}` })
				.then(media => {
					setFileData({
						id: media.id,
						url: media.source_url,
						filename: media.title?.rendered || media.source_url.split('/').pop(),
						mime: media.mime_type
					});
				})
				.catch(() => {
					setFileData(null);
				});
		}, [fileId]);

		return el('div', { style: { marginBottom: '20px' } },
			// Label
			field.label && el('label', {
				style: {
					display: 'block',
					marginBottom: '8px',
					fontWeight: '600',
					fontSize: '13px'
				}
			}, field.label),

			// File info and preview
			el(MediaUpload, {
				onSelect: (media) => setAttributes({ [fieldId]: media.id.toString() }),
				allowedTypes: allowedTypes.length > 0 ? allowedTypes : undefined,
				value: fileId,
				render: ({ open }) => {
					return el('div', {},
						// File preview
						fileData && el('div', {
							style: {
								padding: '12px',
								backgroundColor: '#f0f0f0',
								borderRadius: '4px',
								marginBottom: '10px',
								display: 'flex',
								alignItems: 'center',
								gap: '12px'
							}
						},
							// File icon
							el('span', {
								style: {
									fontSize: '24px'
								}
							}, '📄'),
							// File info
							el('div', { style: { flex: 1, minWidth: 0 } },
								el('div', {
									style: {
										fontWeight: '600',
										fontSize: '14px',
										overflow: 'hidden',
										textOverflow: 'ellipsis',
										whiteSpace: 'nowrap'
									}
								}, fileData.filename),
								el('div', {
									style: {
										fontSize: '12px',
										color: '#666',
										marginTop: '2px'
									}
								}, fileData.mime),
								el('a', {
									href: fileData.url,
									target: '_blank',
									rel: 'noopener noreferrer',
									style: {
										fontSize: '11px',
										color: '#0073aa',
										marginTop: '4px',
										display: 'block'
									}
								}, 'View file')
							)
						),
						// Buttons
						el('div', { style: { marginTop: fileData ? '0' : '0' } },
							el(Button, {
								onClick: open,
								variant: fileId ? 'secondary' : 'primary'
							}, fileId ? 'Change File' : 'Select File'),
							fileId && el(Button, {
								onClick: () => setAttributes({ [fieldId]: '' }),
								isDestructive: true,
								variant: 'secondary',
								style: { marginLeft: '10px' }
							}, 'Remove')
						)
					);
				}
			}),

			// Description
			field.description && el('p', {
				style: {
					marginTop: '8px',
					marginBottom: '0',
					fontSize: '12px',
					fontStyle: 'italic',
					color: '#757575'
				}
			}, field.description)
		);
	};

	/**
	 * Post Object Field Component
	 * Autocomplete select for posts with type filtering
	 */
	const PostObjectField = ({ field, value, setAttributes, fieldId }) => {
		const { TextControl, Button, Spinner } = wp.components;
		const postId = value ? parseInt(value, 10) : 0;
		const [posts, setPosts] = useState([]);
		const [selectedPost, setSelectedPost] = useState(null);
		const [searchTerm, setSearchTerm] = useState('');
		const [loading, setLoading] = useState(false);
		const [isOpen, setIsOpen] = useState(false);

		// Get post types from field config (comma-separated string or array)
		const postTypes = field.post_types || 'global_blocks';
		const postTypesStr = Array.isArray(postTypes) ? postTypes.join(',') : postTypes;

		// Fetch selected post details when ID changes
		useEffect(() => {
			if (!postId) {
				setSelectedPost(null);
				return;
			}

			wp.apiFetch({ path: `/webtero/v1/posts/autocomplete?post_types=${postTypesStr}&per_page=1` })
				.then(results => {
					const post = results.find(p => p.id === postId);
					if (post) {
						setSelectedPost(post);
					}
				})
				.catch(() => setSelectedPost(null));
		}, [postId, postTypesStr]);

		// Search posts
		const searchPosts = useCallback((search) => {
			if (search.length < 2) {
				setPosts([]);
				return;
			}

			setLoading(true);
			wp.apiFetch({
				path: `/webtero/v1/posts/autocomplete?search=${encodeURIComponent(search)}&post_types=${postTypesStr}&per_page=20`
			})
				.then(results => {
					setPosts(results);
					setLoading(false);
				})
				.catch(() => {
					setPosts([]);
					setLoading(false);
				});
		}, [postTypesStr]);

		// Handle search input change
		const handleSearchChange = useCallback((newSearch) => {
			setSearchTerm(newSearch);
			searchPosts(newSearch);
		}, [searchPosts]);

		// Select post
		const selectPost = useCallback((post) => {
			setAttributes({ [fieldId]: post.id.toString() });
			setSelectedPost(post);
			setSearchTerm('');
			setPosts([]);
			setIsOpen(false);
		}, [fieldId, setAttributes]);

		// Clear selection
		const clearSelection = useCallback(() => {
			setAttributes({ [fieldId]: '' });
			setSelectedPost(null);
			setSearchTerm('');
			setPosts([]);
		}, [fieldId, setAttributes]);

		return el('div', { style: { marginBottom: '20px' } },
			// Label
			field.label && el('label', {
				style: {
					display: 'block',
					marginBottom: '8px',
					fontWeight: '600',
					fontSize: '13px'
				}
			}, field.label),

			// Selected post display
			selectedPost && !isOpen && el('div', {
				style: {
					padding: '12px',
					backgroundColor: '#f0f0f0',
					borderRadius: '4px',
					marginBottom: '10px'
				}
			},
				el('div', {
					style: {
						fontWeight: '600',
						fontSize: '14px',
						marginBottom: '8px'
					}
				}, selectedPost.title),
				el('div', {
					style: {
						fontSize: '12px',
						color: '#666',
						marginBottom: '8px'
					}
				}, `ID: ${selectedPost.id} | Type: ${selectedPost.post_type}`),
				// Edit link
				el('a', {
					href: selectedPost.edit_link,
					target: '_blank',
					rel: 'noopener noreferrer',
					style: {
						fontSize: '12px',
						color: '#0073aa',
						textDecoration: 'none',
						display: 'inline-block',
						marginRight: '12px'
					}
				}, 'Edit Post →'),
				// Change button
				el(Button, {
					onClick: () => setIsOpen(true),
					variant: 'secondary',
					isSmall: true,
					style: { marginRight: '8px' }
				}, 'Change'),
				// Remove button
				el(Button, {
					onClick: clearSelection,
					variant: 'secondary',
					isDestructive: true,
					isSmall: true
				}, 'Remove')
			),

			// Search interface (when no selection or changing)
			(!selectedPost || isOpen) && el('div', {},
				// Search input
				el(TextControl, {
					value: searchTerm,
					onChange: handleSearchChange,
					placeholder: 'Type to search...',
					autoFocus: isOpen
				}),

				// Loading indicator
				loading && el('div', { style: { padding: '8px' } },
					el(Spinner)
				),

				// Results list
				posts.length > 0 && !loading && el('div', {
					style: {
						maxHeight: '200px',
						overflowY: 'auto',
						border: '1px solid #ddd',
						borderRadius: '4px',
						marginTop: '8px'
					}
				},
					posts.map(post =>
						el('div', {
							key: post.id,
							onClick: () => selectPost(post),
							style: {
								padding: '10px',
								cursor: 'pointer',
								borderBottom: '1px solid #f0f0f0',
								transition: 'background-color 0.2s'
							},
							onMouseEnter: (e) => e.target.style.backgroundColor = '#f5f5f5',
							onMouseLeave: (e) => e.target.style.backgroundColor = 'transparent'
						},
							el('div', { style: { fontWeight: '600', fontSize: '14px' } }, post.title),
							el('div', { style: { fontSize: '12px', color: '#666', marginTop: '4px' } },
								`ID: ${post.id} | ${post.post_type}`
							)
						)
					)
				),

				// Cancel button (when changing)
				isOpen && selectedPost && el(Button, {
					onClick: () => {
						setIsOpen(false);
						setSearchTerm('');
						setPosts([]);
					},
					variant: 'secondary',
					style: { marginTop: '8px' }
				}, 'Cancel')
			),

			// Description
			field.description && el('p', {
				style: {
					marginTop: '8px',
					marginBottom: '0',
					fontSize: '12px',
					fontStyle: 'italic',
					color: '#757575'
				}
			}, field.description)
		);
	};

	/**
	 * Gallery Field Component
	 * Multiple image selection with reordering
	 */
	const GalleryField = ({ field, value, setAttributes, fieldId, setAttributesFn }) => {
		const { MediaUpload } = wp.blockEditor || wp.editor;
		const { Button } = wp.components;
		const imageIds = Array.isArray(value) ? value : [];
		const [images, setImages] = useState([]);

		// Fetch image data when IDs change
		useEffect(() => {
			if (imageIds.length === 0) {
				setImages([]);
				return;
			}

			// Fetch all images via REST API
			Promise.all(
				imageIds.map(id =>
					wp.apiFetch({ path: `/wp/v2/media/${id}` })
						.catch(() => null) // Handle errors (deleted images, etc.)
				)
			).then(results => {
				// Filter out null results and map to our format
				const validImages = results
					.filter(img => img !== null)
					.map(img => ({
						id: img.id,
						url: img.media_details?.sizes?.thumbnail?.source_url || img.source_url,
						alt: img.alt_text || ''
					}));
				setImages(validImages);
			});
		}, [imageIds]);

		// Handle media selection
		const onSelectImages = useCallback((media) => {
			const newIds = media.map(m => m.id);
			setAttributesFn({ [fieldId]: newIds });
		}, [fieldId, setAttributesFn]);

		// Move image up or down
		const moveImage = useCallback((index, direction) => {
			const newIds = [...imageIds];
			const targetIndex = direction === 'up' ? index - 1 : index + 1;

			if (targetIndex < 0 || targetIndex >= newIds.length) return;

			// Swap IDs
			[newIds[index], newIds[targetIndex]] = [newIds[targetIndex], newIds[index]];

			setAttributesFn({ [fieldId]: newIds });
		}, [imageIds, fieldId, setAttributesFn]);

		// Remove image
		const removeImage = useCallback((index) => {
			const newIds = imageIds.filter((_, i) => i !== index);
			setAttributesFn({ [fieldId]: newIds });
		}, [imageIds, fieldId, setAttributesFn]);

		return el('div', { style: { marginBottom: '20px' } },
			// Label
			field.label && el('label', {
				style: {
					display: 'block',
					marginBottom: '8px',
					fontWeight: '600',
					fontSize: '13px'
				}
			}, field.label),

			// Image grid
			images.length > 0 && el('div', {
				style: {
					display: 'grid',
					gridTemplateColumns: 'repeat(auto-fill, minmax(120px, 1fr))',
					gap: '12px',
					marginBottom: '12px'
				}
			},
				images.map((img, index) =>
					el('div', {
						key: img.id,
						style: {
							position: 'relative',
							border: '1px solid #ddd',
							borderRadius: '4px',
							overflow: 'hidden',
							backgroundColor: '#f0f0f0'
						}
					},
						// Image
						el('img', {
							src: img.url,
							alt: img.alt,
							style: {
								width: '100%',
								height: '100px',
								objectFit: 'cover',
								display: 'block'
							}
						}),

						// Controls overlay
						el('div', {
							style: {
								position: 'absolute',
								top: '4px',
								right: '4px',
								display: 'flex',
								gap: '4px',
								flexDirection: 'column'
							}
						},
							// Move up button
							index > 0 && el(Button, {
								icon: el('svg', {
									width: 16,
									height: 16,
									viewBox: '0 0 24 24',
									fill: 'none',
									xmlns: 'http://www.w3.org/2000/svg'
								}, el('path', {
									d: 'M18 15L12 9L6 15',
									stroke: 'currentColor',
									strokeWidth: '2',
									strokeLinecap: 'round',
									strokeLinejoin: 'round'
								})),
								onClick: () => moveImage(index, 'up'),
								isSmall: true,
								style: {
									minWidth: '24px',
									height: '24px',
									backgroundColor: 'rgba(255, 255, 255, 0.9)',
									border: '1px solid #ddd'
								}
							}),

							// Move down button
							index < images.length - 1 && el(Button, {
								icon: el('svg', {
									width: 16,
									height: 16,
									viewBox: '0 0 24 24',
									fill: 'none',
									xmlns: 'http://www.w3.org/2000/svg'
								}, el('path', {
									d: 'M6 9L12 15L18 9',
									stroke: 'currentColor',
									strokeWidth: '2',
									strokeLinecap: 'round',
									strokeLinejoin: 'round'
								})),
								onClick: () => moveImage(index, 'down'),
								isSmall: true,
								style: {
									minWidth: '24px',
									height: '24px',
									backgroundColor: 'rgba(255, 255, 255, 0.9)',
									border: '1px solid #ddd'
								}
							}),

							// Remove button
							el(Button, {
								icon: el('svg', {
									width: 16,
									height: 16,
									viewBox: '0 0 24 24',
									fill: 'none',
									xmlns: 'http://www.w3.org/2000/svg'
								}, el('path', {
									d: 'M3 6h18M8 6V4a1 1 0 011-1h6a1 1 0 011 1v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14zM10 11v6m4-6v6',
									stroke: 'currentColor',
									strokeWidth: '2',
									strokeLinecap: 'round',
									strokeLinejoin: 'round'
								})),
								onClick: () => removeImage(index),
								isSmall: true,
								isDestructive: true,
								style: {
									minWidth: '24px',
									height: '24px',
									backgroundColor: 'rgba(255, 255, 255, 0.9)',
									border: '1px solid #ddd'
								}
							})
						)
					)
				)
			),

			// Select/Edit button
			el(MediaUpload, {
				onSelect: onSelectImages,
				allowedTypes: ['image'],
				multiple: true,
				gallery: true,
				value: imageIds,
				render: ({ open }) => el(Button, {
					onClick: open,
					variant: 'secondary'
				}, imageIds.length > 0 ? 'Edit Gallery' : 'Select Images')
			}),

			// Description
			field.description && el('p', {
				style: {
					marginTop: '8px',
					marginBottom: '0',
					fontSize: '12px',
					fontStyle: 'italic',
					color: '#757575'
				}
			}, field.description)
		);
	};

	/**
	 * Repeater Field Component
	 * Inline repeater with grid layout and arrow button reordering
	 */
	const RepeaterField = ({ field, value, setAttributes, fieldId, renderFieldFn }) => {
		const rows = Array.isArray(value) ? value : [];

		// Use ref to always have access to latest rows value (prevents stale closures in TipTap onChange)
		const rowsRef = useRef(rows);
		rowsRef.current = rows;

		// Ensure all rows have unique _rowId (for existing data without IDs)
		useState(() => {
			let needsUpdate = false;
			const updatedRows = rows.map((row, index) => {
				if (!row._rowId) {
					needsUpdate = true;
					return {
						...row,
						_rowId: `row_${Date.now()}_${index}_${Math.random().toString(36).substring(2, 11)}`
					};
				}
				return row;
			});

			if (needsUpdate) {
				setAttributes({ [fieldId]: updatedRows });
			}
		}, []);

		// Track collapsed state for each row (local UI state, not saved to attributes)
		const [collapsedRows, setCollapsedRows] = useState({});

		// Get min/max constraints
		const minRows = field.min || 0;
		const maxRows = field.max || 999;

		// Add new row with default values
		const addRow = useCallback(() => {
			if (rows.length >= maxRows) return;

			const newRow = {
				_width: 100,
				_rowId: `row_${Date.now()}_${Math.random().toString(36).substring(2, 11)}`
			};
			// Initialize with default values from field definitions
			(field.fields || []).forEach(subField => {
				newRow[subField.id] = subField.default !== undefined ? subField.default : '';
			});

			const newRows = [...rows, newRow];
			setAttributes({ [fieldId]: newRows });
		}, [rows, maxRows, field.fields, fieldId, setAttributes]);

		// Add row before or after specific index
		const addRowAt = useCallback((index, position) => {
			if (rows.length >= maxRows) return;

			const newRow = {
				_width: 100,
				_rowId: `row_${Date.now()}_${Math.random().toString(36).substring(2, 11)}`
			};
			// Initialize with default values from field definitions
			(field.fields || []).forEach(subField => {
				newRow[subField.id] = subField.default !== undefined ? subField.default : '';
			});

			const newRows = [...rows];
			const insertIndex = position === 'before' ? index : index + 1;
			newRows.splice(insertIndex, 0, newRow);
			setAttributes({ [fieldId]: newRows });
		}, [rows, maxRows, field.fields, fieldId, setAttributes]);

		// Delete row (called from dropdown confirmation)
		const deleteRow = useCallback((index) => {
			if (rows.length <= minRows) return;

			const newRows = rows.filter((_, i) => i !== index);
			setAttributes({ [fieldId]: newRows });
		}, [rows, minRows, fieldId, setAttributes]);

		// Update row field value
		const updateRowField = useCallback((rowIndex, subFieldId, newValue) => {
			// Use rowsRef.current to get the latest rows value (prevents stale closure issues)
			const currentRows = rowsRef.current;
			const newRows = [...currentRows];
			newRows[rowIndex] = { ...newRows[rowIndex], [subFieldId]: newValue };
			setAttributes({ [fieldId]: newRows });
		}, [fieldId, setAttributes, rowsRef]);

		// Update row width
		const updateRowWidth = useCallback((rowIndex, newWidth) => {
			// Use rowsRef.current to get the latest rows value (prevents stale closure issues)
			const currentRows = rowsRef.current;
			const newRows = [...currentRows];
			newRows[rowIndex] = { ...newRows[rowIndex], _width: newWidth };
			setAttributes({ [fieldId]: newRows });
		}, [fieldId, setAttributes, rowsRef]);

		// Move row up or down
		const moveRow = useCallback((index, direction) => {
			const newRows = [...rows];
			const targetIndex = direction === 'up' ? index - 1 : index + 1;

			// Swap rows
			[newRows[index], newRows[targetIndex]] = [newRows[targetIndex], newRows[index]];

			setAttributes({ [fieldId]: newRows });
		}, [rows, fieldId, setAttributes]);

		// Toggle row collapse state
		const toggleRowCollapse = useCallback((index) => {
			setCollapsedRows(prev => ({
				...prev,
				[index]: !prev[index]
			}));
		}, []);

		// Render a single row with fields always visible
		const renderRow = (row, index) => {
			const subFields = field.fields || [];
			const rowWidth = row._width || 100;
			const rowId = row._rowId || `row-${index}`;
			const isCollapsed = collapsedRows[index] || false;

			return el('div', {
				key: rowId,
				style: {
					position: 'relative',
					width: `${rowWidth}%`,
					display: 'inline-block',
					verticalAlign: 'top',
					paddingRight: rowWidth < 100 ? '16px' : '0',
					boxSizing: 'border-box',
					marginBottom: '16px'
				}
			},
				el('div', {
					style: {
						padding: '16px',
						backgroundColor: '#fff',
						border: '1px solid #ddd',
						borderRadius: '4px',
						position: 'relative'
					}
				},
					// Header with row number, reorder buttons, width selector, and delete
					el('div', {
						style: {
							display: 'flex',
							alignItems: 'center',
							gap: '8px',
							marginBottom: isCollapsed ? '0' : '12px',
							paddingBottom: isCollapsed ? '0' : '8px',
							borderBottom: isCollapsed ? 'none' : '1px solid #e0e0e0'
						}
					},
						// Row number
						el('span', {
							style: {
								fontSize: '12px',
								fontWeight: 600,
								color: '#666'
							}
						}, `Row ${index + 1}`),

						// Collapse/expand toggle button
						el(Button, {
							onClick: () => toggleRowCollapse(index),
							isSmall: true,
							label: isCollapsed ? 'Expand row' : 'Collapse row',
							showTooltip: true
						},
							// Eye icon (open or closed)
							el('svg', {
								xmlns: 'http://www.w3.org/2000/svg',
								viewBox: '0 0 24 24',
								width: '20',
								height: '20',
								'aria-hidden': 'true',
								focusable: 'false'
							},
								el('path', {
									d: isCollapsed
										? 'M20.7 12.7s0-.1-.1-.2c0-.2-.2-.4-.4-.6-.3-.5-.9-1.2-1.6-1.8-.7-.6-1.5-1.3-2.6-1.8l-.6 1.4c.9.4 1.6 1 2.1 1.5.6.6 1.1 1.2 1.4 1.6.1.2.3.4.3.5v.1l.7-.3.7-.3Zm-5.2-9.3-1.8 4c-.5-.1-1.1-.2-1.7-.2-3 0-5.2 1.4-6.6 2.7-.7.7-1.2 1.3-1.6 1.8-.2.3-.3.5-.4.6 0 0 0 .1-.1.2s0 0 .7.3l.7.3V13c0-.1.2-.3.3-.5.3-.4.7-1 1.4-1.6 1.2-1.2 3-2.3 5.5-2.3H13v.3c-.4 0-.8-.1-1.1-.1-1.9 0-3.5 1.6-3.5 3.5s.6 2.3 1.6 2.9l-2 4.4.9.4 7.6-16.2-.9-.4Zm-3 12.6c1.7-.2 3-1.7 3-3.5s-.2-1.4-.6-1.9L12.4 16Z'
										: 'M3.99961 13C4.67043 13.3354 4.6703 13.3357 4.67017 13.3359L4.67298 13.3305C4.67621 13.3242 4.68184 13.3135 4.68988 13.2985C4.70595 13.2686 4.7316 13.2218 4.76695 13.1608C4.8377 13.0385 4.94692 12.8592 5.09541 12.6419C5.39312 12.2062 5.84436 11.624 6.45435 11.0431C7.67308 9.88241 9.49719 8.75 11.9996 8.75C14.502 8.75 16.3261 9.88241 17.5449 11.0431C18.1549 11.624 18.6061 12.2062 18.9038 12.6419C19.0523 12.8592 19.1615 13.0385 19.2323 13.1608C19.2676 13.2218 19.2933 13.2686 19.3093 13.2985C19.3174 13.3135 19.323 13.3242 19.3262 13.3305L19.3291 13.3359C19.3289 13.3357 19.3288 13.3354 19.9996 13C20.6704 12.6646 20.6703 12.6643 20.6701 12.664L20.6697 12.6632L20.6688 12.6614L20.6662 12.6563L20.6583 12.6408C20.6517 12.6282 20.6427 12.6108 20.631 12.5892C20.6078 12.5459 20.5744 12.4852 20.5306 12.4096C20.4432 12.2584 20.3141 12.0471 20.1423 11.7956C19.7994 11.2938 19.2819 10.626 18.5794 9.9569C17.1731 8.61759 14.9972 7.25 11.9996 7.25C9.00203 7.25 6.82614 8.61759 5.41987 9.9569C4.71736 10.626 4.19984 11.2938 3.85694 11.7956C3.68511 12.0471 3.55605 12.2584 3.4686 12.4096C3.42484 12.4852 3.39142 12.5459 3.36818 12.5892C3.35656 12.6108 3.34748 12.6282 3.34092 12.6408L3.33297 12.6563L3.33041 12.6614L3.32948 12.6632L3.32911 12.664C3.32894 12.6643 3.32879 12.6646 3.99961 13ZM11.9996 16C13.9326 16 15.4996 14.433 15.4996 12.5C15.4996 10.567 13.9326 9 11.9996 9C10.0666 9 8.49961 10.567 8.49961 12.5C8.49961 14.433 10.0666 16 11.9996 16Z'
								})
							)
						),

						el('div', { style: { flex: 1 } }),

						// Row controls wrapper
						el('div', {
							style: { display: 'flex', gap: '4px', alignItems: 'center' }
						},
							// Move up button
							el(Button, {
								onClick: () => moveRow(index, 'up'),
								isSmall: true,
								disabled: index === 0,
								label: 'Move up',
								showTooltip: true
							},
								// SVG up arrow
								el('svg', {
									xmlns: 'http://www.w3.org/2000/svg',
									viewBox: '0 0 24 24',
									width: '20',
									height: '20',
									'aria-hidden': 'true',
									focusable: 'false'
								},
									el('path', {
										d: 'M12 7.5l-6 6 1.5 1.5 4.5-4.5 4.5 4.5 1.5-1.5z'
									})
								)
							),

							// Move down button
							el(Button, {
								onClick: () => moveRow(index, 'down'),
								isSmall: true,
								disabled: index === rows.length - 1,
								label: 'Move down',
								showTooltip: true
							},
								// SVG down arrow
								el('svg', {
									xmlns: 'http://www.w3.org/2000/svg',
									viewBox: '0 0 24 24',
									width: '20',
									height: '20',
									'aria-hidden': 'true',
									focusable: 'false'
								},
									el('path', {
										d: 'M12 16.5l6-6-1.5-1.5-4.5 4.5-4.5-4.5-1.5 1.5z'
									})
								)
							),

							// Add row dropdown (before/after)
							el(Dropdown, {
								popoverProps: { placement: 'bottom-end' },
								renderToggle: ({ isOpen, onToggle }) => el(Button, {
									onClick: onToggle,
									isSmall: true,
									variant: 'secondary',
									disabled: rows.length >= maxRows,
									label: 'Add row',
									showTooltip: true,
									'aria-expanded': isOpen
								},
									// Plus icon SVG
									el('svg', {
										xmlns: 'http://www.w3.org/2000/svg',
										viewBox: '0 0 24 24',
										width: '20',
										height: '20',
										'aria-hidden': 'true',
										focusable: 'false'
									},
										el('path', {
											d: 'M18.5 5.5V8H20V5.5h2.5V4H20V1.5h-1.5V4H16v1.5h2.5zM12 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2v-6h-1.5v6a.5.5 0 01-.5.5H6a.5.5 0 01-.5-.5V6a.5.5 0 01.5-.5h6V4z'
										})
									)
								),
								renderContent: ({ onClose }) => el('div', {
									style: {
										padding: '8px',
										minWidth: '140px'
									}
								},
									el('div', {
										style: {
											fontSize: '11px',
											fontWeight: 600,
											marginBottom: '8px',
											color: '#666'
										}
									}, 'Add Row'),
									el(Button, {
										onClick: () => {
											addRowAt(index, 'before');
											onClose();
										},
										variant: 'secondary',
										isSmall: true,
										style: {
											width: '100%',
											marginBottom: '4px',
											justifyContent: 'flex-start'
										}
									}, 'Add Before'),
									el(Button, {
										onClick: () => {
											addRowAt(index, 'after');
											onClose();
										},
										variant: 'secondary',
										isSmall: true,
										style: {
											width: '100%',
											justifyContent: 'flex-start'
										}
									}, 'Add After')
								)
							}),

							// Width dropdown
							el(Dropdown, {
							popoverProps: { placement: 'bottom-end' },
							renderToggle: ({ isOpen, onToggle }) => el(Button, {
								onClick: onToggle,
								isSmall: true,
								variant: 'secondary',
								icon: 'image-flip-horizontal',
								'aria-expanded': isOpen
							}, `${rowWidth}%`),
							renderContent: ({ onClose }) => el('div', {
								style: {
									padding: '8px',
									minWidth: '120px'
								}
							},
								el('div', {
									style: {
										fontSize: '11px',
										fontWeight: 600,
										marginBottom: '8px',
										color: '#666'
									}
								}, 'Row Width'),
								[25, 33, 50, 66, 100].map(width => el(Button, {
									key: width,
									onClick: () => {
										updateRowWidth(index, width);
										onClose();
									},
									variant: rowWidth === width ? 'primary' : 'secondary',
									isSmall: true,
									style: {
										width: '100%',
										marginBottom: '4px',
										justifyContent: 'center'
									}
								}, `${width}%`))
							)
						}),

						// Delete button with dropdown confirmation
						el(Dropdown, {
							popoverProps: { placement: 'bottom-end' },
							renderToggle: ({ isOpen, onToggle }) => el(Button, {
								onClick: onToggle,
								isSmall: true,
								isDestructive: true,
								disabled: rows.length <= minRows,
								label: 'Delete',
								showTooltip: true,
								'aria-expanded': isOpen
							},
								// SVG trash icon
								el('svg', {
									xmlns: 'http://www.w3.org/2000/svg',
									viewBox: '0 0 24 24',
									width: '20',
									height: '20',
									'aria-hidden': 'true',
									focusable: 'false'
								},
									el('path', {
										fillRule: 'evenodd',
										clipRule: 'evenodd',
										d: 'M12 5.5A2.25 2.25 0 0 0 9.878 7h4.244A2.251 2.251 0 0 0 12 5.5ZM12 4a3.751 3.751 0 0 0-3.675 3H5v1.5h1.27l.818 8.997a2.75 2.75 0 0 0 2.739 2.501h4.347a2.75 2.75 0 0 0 2.738-2.5L17.73 8.5H19V7h-3.325A3.751 3.751 0 0 0 12 4Zm4.224 4.5H7.776l.806 8.861a1.25 1.25 0 0 0 1.245 1.137h4.347a1.25 1.25 0 0 0 1.245-1.137l.805-8.861Z'
									})
								)
							),
							renderContent: ({ onClose }) => el('div', {
								style: {
									padding: '12px',
									minWidth: '160px'
								}
							},
								el('div', {
									style: {
										fontSize: '12px',
										marginBottom: '10px',
										color: '#666'
									}
								}, 'Are you sure?'),
								el(Button, {
									onClick: () => {
										deleteRow(index);
										onClose();
									},
									isDestructive: true,
									variant: 'primary',
									isSmall: true,
									style: {
										width: '100%',
										justifyContent: 'center',
										color: '#fff'
									}
								}, 'Confirm Delete')
							)
						})
						) // Close row controls wrapper
					),

					// Fields in a grid (only render when not collapsed)
					!isCollapsed && el('div', {
						style: {
							display: 'flex',
							flexWrap: 'wrap',
							marginRight: '-16px'
						}
					},
						subFields.map(subField => {
							// Get current value
							const currentValue = row[subField.id] !== undefined
								? row[subField.id]
								: (subField.default !== undefined ? subField.default : '');

							// Create a temporary setAttributes that updates this specific row
							const tempSetAttributes = (updates) => {
								const updatedFieldId = Object.keys(updates)[0];
								const newValue = updates[updatedFieldId];
								updateRowField(index, updatedFieldId, newValue);
							};

							// Render the field content with wrapper
							const fieldContent = renderFieldFn(subField, currentValue, tempSetAttributes);
							const fieldWidth = subField.width || 100;

							return el('div', {
								key: `${rowId}-${subField.id}`,
								style: {
									width: `${fieldWidth}%`,
									paddingRight: fieldWidth < 100 ? '16px' : '0',
									boxSizing: 'border-box',
									display: 'inline-block',
									verticalAlign: 'top'
								}
							}, fieldContent);
						})
					)
				)
			);
		};

		// Main render
		return el('div', {
			style: { marginBottom: '20px' }
		},
			// Label and counter
			el('div', {
				style: {
					display: 'flex',
					justifyContent: 'space-between',
					alignItems: 'center',
					marginBottom: '12px'
				}
			},
				el('label', {
					style: {
						fontSize: '11px',
						fontWeight: 600,
						textTransform: 'uppercase'
					}
				}, field.label),
				el('span', {
					style: {
						fontSize: '12px',
						color: '#666'
					}
				}, `${rows.length} / ${maxRows} rows`)
			),

			// Description
			field.description && el('p', {
				style: {
					marginTop: '-8px',
					marginBottom: '12px',
					fontSize: '12px',
					color: '#757575'
				}
			}, field.description),

			// Rows container with flexbox wrapping
			rows.length > 0 && el('div', {
				style: {
					display: 'flex',
					flexWrap: 'wrap',
					marginRight: '-16px',
					marginBottom: '12px'
				}
			},
				rows.map((row, index) => renderRow(row, index))
			),

			// Add button
			el(Button, {
				icon: 'plus-alt',
				variant: 'secondary',
				disabled: rows.length >= maxRows,
				onClick: addRow
			}, 'Add Row'),

			// Show min rows warning
			minRows > 0 && rows.length < minRows && el('p', {
				style: {
					marginTop: '8px',
					fontSize: '12px',
					color: '#d63638'
				}
			}, `Minimum ${minRows} rows required`)
		);
	};

	/**
	 * Universal edit component factory
	 * Creates edit component for any block based on PHP field definitions
	 */
	function createUniversalEdit(blockName) {
		return function UniversalEdit(props) {
			const { attributes, setAttributes, isSelected, clientId } = props;
			const blockProps = useBlockProps();
			const [fields, setFields] = useState(null);
			const [loading, setLoading] = useState(true);
			const [error, setError] = useState(null);

			// Debug: Log current attributes
			// console.log(`[${blockName}] Current attributes:`, attributes);

			// Get stable block identifier for localStorage
			// Uses post ID + block index to create consistent key across refreshes
			const { postId, blockIndex } = useSelect((select) => {
				const { getBlockOrder, getBlockRootClientId } = select('core/block-editor');
				const { getCurrentPostId } = select('core/editor');
				const rootClientId = getBlockRootClientId(clientId);
				const blockOrder = getBlockOrder(rootClientId);
				const index = blockOrder.indexOf(clientId);

				return {
					postId: getCurrentPostId() || 'new',
					blockIndex: index !== -1 ? index : 0
				};
			}, [clientId]);

			// Create stable storage key using post ID + block index
			const storageKey = `webtero_block_preview_${postId}_${blockIndex}`;

			// Initialize preview state - default to false (edit mode)
			const [isPreview, setIsPreview] = useState(false);

			// Load and sync preview state from localStorage when storage key is ready
			useEffect(() => {
				// Only load from localStorage when we have a valid post ID
				if (postId && postId !== 'new') {
					const savedState = localStorage.getItem(storageKey);
					// console.log('Storage key:', storageKey, 'Saved state:', savedState);
					// If there's a saved state, use it; otherwise stay in edit mode (false)
					const newState = savedState === 'true';
					// console.log('Setting isPreview to:', newState);
					setIsPreview(newState);
				}
			}, [storageKey, postId]);

			// Check if this is an example/preview (block inserter)
			// Block inserter passes example data via attributes.__unstableBlockSource
			const isExample = attributes.__unstableBlockSource === 'inserter';

			// Save preview state to localStorage whenever it changes
			const togglePreview = (newPreviewState) => {
				setIsPreview(newPreviewState);
				localStorage.setItem(storageKey, newPreviewState.toString());
			};

			// Fetch block fields from PHP (only if not an example preview)
			useEffect(() => {
				// Skip field fetching for block inserter previews
				if (isExample) {
					setLoading(false);
					return;
				}

				wp.apiFetch({
					path: `/webtero/v1/block-fields-editor/${blockName}`,
					method: 'GET'
				}).then((response) => {
					if (response.success && response.fields) {
						setFields(response.fields);
					} else {
						setError('Failed to load block fields');
					}
					setLoading(false);
				}).catch((err) => {
					console.error('Failed to load block fields:', err);
					setError(err.message || 'Unknown error');
					setLoading(false);
				});
			}, [isExample]);

			/**
			 * Render a single field content (without wrapper)
			 * Used by both main renderField and RepeaterField component
			 */
			const renderFieldContent = (field, value, setAttributesFn) => {
				const fieldId = field.id;

				// Render the field based on type
				let fieldContent;
				switch (field.type) {
					case 'text':
						fieldContent = el(TextControl, {
							key: `${fieldId}-control`,
							label: field.label,
							value: value,
							onChange: (newValue) => setAttributesFn({ [fieldId]: newValue }),
							placeholder: field.placeholder || '',
							help: field.help || '',
							__nextHasNoMarginBottom: true,
							__next40pxDefaultSize: true
						});
						break;

					case 'textarea':
						fieldContent = el(TextareaControl, {
							key: fieldId,
							label: field.label,
							value: value,
							onChange: (newValue) => setAttributesFn({ [fieldId]: newValue }),
							placeholder: field.placeholder || '',
							help: field.help || '',
							rows: field.rows || 5,
							__nextHasNoMarginBottom: true
						});
						break;

					case 'number':
						fieldContent = el(TextControl, {
							key: fieldId,
							label: field.label,
							type: 'number',
							value: value,
							onChange: (newValue) => setAttributesFn({ [fieldId]: parseFloat(newValue) || 0 }),
							min: field.min,
							max: field.max,
							step: field.step || 1,
							help: field.description || '',
							__nextHasNoMarginBottom: true,
							__next40pxDefaultSize: true
						});
						break;

					case 'range':
						fieldContent = el(RangeControl, {
							key: fieldId,
							label: field.label,
							value: value,
							onChange: (newValue) => setAttributesFn({ [fieldId]: newValue }),
							min: field.min || 0,
							max: field.max || 100,
							step: field.step || 1,
							help: field.description || '',
							__nextHasNoMarginBottom: true,
							__next40pxDefaultSize: true
						});
						break;

					case 'radio':
						fieldContent = el(RadioControl, {
							key: fieldId,
							label: field.label,
							selected: value,
							options: Object.entries(field.options || {}).map(([val, label]) => ({
								label: label,
								value: val
							})),
							onChange: (newValue) => setAttributesFn({ [fieldId]: newValue }),
							help: field.description || '',
							__nextHasNoMarginBottom: true
						});
						break;

					case 'checkbox':
						fieldContent = el(CheckboxControl, {
							key: fieldId,
							label: field.checkbox_label || field.label,
							checked: !!value,
							onChange: (newValue) => setAttributesFn({ [fieldId]: newValue }),
							help: field.description || '',
							__nextHasNoMarginBottom: true
						});
						break;

					case 'toggle':
						fieldContent = el(ToggleControl, {
							key: fieldId,
							label: field.label,
							checked: !!value,
							onChange: (newValue) => setAttributesFn({ [fieldId]: newValue }),
							help: field.description || '',
							__nextHasNoMarginBottom: true
						});
						break;

					case 'button_group':
						const isMultiple = field.multiple || false;
						const currentValues = isMultiple ? (Array.isArray(value) ? value : []) : value;

						fieldContent = el('div', {
							key: fieldId,
							style: { marginBottom: '20px' }
						},
							el('label', {
								style: {
									display: 'block',
									marginBottom: '8px',
									fontSize: '11px',
									fontWeight: 600,
									textTransform: 'uppercase'
								}
							}, field.label),
							el('div', {
								style: {
									display: 'inline-flex',
									gap: '0'
								}
							},
								Object.entries(field.options || {}).map(([optValue, optLabel]) => {
									const label = typeof optLabel === 'object' ? optLabel.label : optLabel;
									const isActive = isMultiple
										? currentValues.includes(optValue)
										: currentValues === optValue;

									return el(Button, {
										key: optValue,
										variant: isActive ? 'primary' : 'secondary',
										onClick: () => {
											if (isMultiple) {
												const newValues = isActive
													? currentValues.filter(v => v !== optValue)
													: [...currentValues, optValue];
												setAttributesFn({ [fieldId]: newValues });
											} else {
												setAttributesFn({ [fieldId]: optValue });
											}
										}
									}, label);
								})
							),
							field.description && el('p', {
								style: {
									marginTop: '8px',
									fontSize: '12px',
									color: '#757575'
								}
							}, field.description)
						);
						break;

					case 'color':
						// Use native color input instead of ColorPicker to avoid errors
						fieldContent = el('div', {
							key: fieldId,
							style: { marginBottom: '20px' }
						},
							el('label', {
								style: {
									display: 'block',
									marginBottom: '8px',
									fontSize: '11px',
									fontWeight: 600,
									textTransform: 'uppercase'
								}
							}, field.label),
							el('div', {
								style: { display: 'flex', gap: '10px', alignItems: 'center' }
							},
								el('input', {
									type: 'color',
									value: value || '#000000',
									onChange: (e) => setAttributesFn({ [fieldId]: e.target.value }),
									style: {
										width: '60px',
										height: '40px',
										border: '1px solid #ddd',
										borderRadius: '4px',
										cursor: 'pointer'
									}
								}),
								el('input', {
									type: 'text',
									value: value || '#000000',
									onChange: (e) => setAttributesFn({ [fieldId]: e.target.value }),
									placeholder: '#000000',
									style: {
										flex: 1,
										padding: '8px',
										border: '1px solid #ddd',
										borderRadius: '4px',
										fontFamily: 'monospace'
									}
								})
							),
							field.description && el('p', {
								style: {
									marginTop: '8px',
									fontSize: '12px',
									color: '#757575'
								}
							}, field.description)
						);
						break;

					case 'select':
						fieldContent = el(SelectControl, {
							key: fieldId,
							label: field.label,
							value: value,
							options: Object.entries(field.options || {}).map(([val, label]) => ({
								label: label,
								value: val
							})),
							onChange: (newValue) => setAttributesFn({ [fieldId]: newValue }),
							help: field.description || '',
							__nextHasNoMarginBottom: true,
							__next40pxDefaultSize: true
						});
						break;

					case 'enhanced_select':
						// For now, render as regular select (can be enhanced later with react-select)
						const isMultipleSelect = field.multiple || false;
						fieldContent = el(SelectControl, {
							key: fieldId,
							label: field.label,
							value: isMultipleSelect ? value : value,
							multiple: isMultipleSelect,
							options: [
								...(isMultipleSelect ? [] : [{ label: field.placeholder || 'Select...', value: '' }]),
								...Object.entries(field.options || {}).map(([val, label]) => ({
									label: label,
									value: val
								}))
							],
							onChange: (newValue) => setAttributesFn({ [fieldId]: newValue }),
							help: field.description || '',
							__nextHasNoMarginBottom: true,
							__next40pxDefaultSize: true
						});
						break;

					case 'media':
						fieldContent = el(MediaField, {
							key: fieldId,
							field: field,
							value: value,
							setAttributes: setAttributesFn,
							fieldId: fieldId
						});
						break;

					case 'file':
						fieldContent = el(FileField, {
							key: fieldId,
							field: field,
							value: value,
							setAttributes: setAttributesFn,
							fieldId: fieldId
						});
						break;

					case 'post_object':
						fieldContent = el(PostObjectField, {
							key: fieldId,
							field: field,
							value: value,
							setAttributes: setAttributesFn,
							fieldId: fieldId
						});
						break;

					case 'gallery':
						fieldContent = el(GalleryField, {
							key: fieldId,
							field: field,
							value: value,
							setAttributes: setAttributesFn,
							fieldId: fieldId,
							setAttributesFn: setAttributesFn
						});
						break;

					case 'tiptap':
						// TipTap field - use component with proper cleanup
						fieldContent = el(TipTapField, {
							key: fieldId,
							fieldId: fieldId,
							value: value,
							label: field.label,
							help: field.help,
							setAttributesFn: setAttributesFn
						});
						break;

					case 'repeater':
						// This case should not be reached when called from repeater
						// but is needed for the top-level field rendering
						fieldContent = null; // Will be handled by renderField wrapper
						break;

					default:
						fieldContent = el('p', {}, `Unsupported field type: ${field.type}`);
				}

				return fieldContent;
			};

			/**
			 * Render a single field with wrapper
			 * Main entry point for rendering fields
			 */
			const renderField = (field) => {
				const fieldId = field.id;
				const value = attributes[fieldId] !== undefined ? attributes[fieldId] : (field.default !== undefined ? field.default : '');
				const fieldWidth = field.width || 100;

				// Special handling for repeater field
				if (field.type === 'repeater') {
					return el('div', {
						key: fieldId,
						style: {
							width: '100%', // Repeaters always full width
							boxSizing: 'border-box'
						}
					},
						el(RepeaterField, {
							field: field,
							value: value,
							setAttributes: setAttributes,
							fieldId: fieldId,
							renderFieldFn: renderFieldContent
						})
					);
				}

				// Render field content
				const fieldContent = renderFieldContent(field, value, setAttributes);

				// Wrap field in container div with custom width
				return el('div', {
					key: fieldId,
					style: {
						width: `${fieldWidth}%`,
						paddingRight: fieldWidth < 100 ? '16px' : '0',
						boxSizing: 'border-box',
						display: 'inline-block',
						verticalAlign: 'top'
					}
				}, fieldContent);
			};

			// Render loading state
			// console.log('Check states - loading:', loading, 'error:', error, 'isExample:', isExample, 'isPreview:', isPreview);

			if (loading) {
				// console.log('Showing loading spinner');
				return el('div', blockProps,
					el('div', {
						style: {
							padding: '40px',
							textAlign: 'center',
							backgroundColor: '#f0f0f0',
							borderRadius: '4px'
						}
					},
						el(Spinner),
						el('p', { style: { marginTop: '10px', color: '#666' } }, 'Loading block...')
					)
				);
			}

			// Render error state
			if (error) {
				return el('div', blockProps,
					el('div', {
						style: {
							padding: '20px',
							backgroundColor: '#f8d7da',
							border: '1px solid #f5c6cb',
							borderRadius: '4px',
							color: '#721c24'
						}
					},
						el('strong', {}, 'Error: '),
						error
					)
				);
			}

			// If this is an example (block inserter preview), show rendered preview only
			if (isExample) {
				return el('div', blockProps,
					el(ServerSideRender, {
						block: blockName,
						attributes: attributes
					})
				);
			}

			// Main render with Edit/Preview toggle in block toolbar
			// console.log('Rendering block - isPreview:', isPreview, 'storageKey:', storageKey);

			return el(Fragment, {},
				// Block toolbar with Edit/Preview toggle
				el(BlockControls, {},
					el(ToolbarGroup, {},
						el(ToolbarButton, {
							icon: 'edit',
							label: 'Edit',
							isPressed: !isPreview,
							onClick: () => togglePreview(false)
						}),
						el(ToolbarButton, {
							icon: 'visibility',
							label: 'Preview',
							isPressed: isPreview,
							onClick: () => togglePreview(true)
						})
					)
				),
				// Main content area
				el('div', blockProps,
					!isPreview ? (
						// Edit mode - show fields
						fields && fields.length > 0 ? el('div', {
							style: {
								padding: '20px',
								backgroundColor: '#fff',
								border: '1px solid #ddd',
								borderRadius: '4px'
							}
						},
							el('div', {
								style: {
									display: 'flex',
									flexWrap: 'wrap',
									marginRight: '-16px' // Offset padding from field containers
								}
							}, fields.map(renderField))
						) : el('p', { style: { padding: '20px', color: '#666' } }, 'No fields configured')
					) : (
						// Preview mode - show server-side render
						el('div', {
							style: {
								minHeight: '100px'
							}
						},
							el(ServerSideRender, {
								block: blockName,
								attributes: attributes,
								EmptyResponsePlaceholder: () => el('div', {
									style: {
										padding: '40px',
										textAlign: 'center',
										backgroundColor: '#f9f9f9',
										borderRadius: '4px',
										border: '2px dashed #ddd'
									}
								}, el('p', { style: { color: '#999' } }, 'No preview available'))
							})
						)
					)
				)
			);
		};
	}

	// Register edit component for each discovered block
	// This will be called by PHP for each registered block
	window.WebteroBlocks = window.WebteroBlocks || {};
	window.WebteroBlocks.createUniversalEdit = createUniversalEdit;
})();
