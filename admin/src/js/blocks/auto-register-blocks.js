/**
 * Auto-Register Blocks
 *
 * Automatically registers all Webtero blocks using the universal editor
 * Block list is provided by PHP via localized script data
 *
 * @package webtero
 */

(function() {
	'use strict';

	// Wait for DOM and universal editor to be ready
	if (!window.WebteroBlocks || !window.WebteroBlocks.createUniversalEdit) {
		console.error('WebteroBlocks.createUniversalEdit not found. Make sure universal-block-editor.js is loaded first.');
		return;
	}

	// Get blocks list from PHP
	const blocksData = window.webteroBlocksData || { blocks: [] };

	// Register each block
	blocksData.blocks.forEach((blockConfig) => {
		const { name, title, icon, category, description, keywords } = blockConfig;

		// Skip if already registered
		if (wp.blocks.getBlockType(name)) {
			// console.log(`Block ${name} already registered, skipping.`);
			return;
		}

		// Register block with universal editor
		wp.blocks.registerBlockType(name, {
			title: title,
			icon: icon || 'admin-page',
			category: category || 'webtero-blocks',
			description: description || '',
			keywords: keywords || [],

			// Use universal edit component
			edit: window.WebteroBlocks.createUniversalEdit(name),

			// Server-side render (no save)
			save: () => null
		});
	});
})();
