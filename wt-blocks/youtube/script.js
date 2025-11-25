/**
 * YouTube Block - Lazy Load Script
 *
 * Handles lazy-loading of YouTube iframes when play button is clicked
 */

(function() {
	'use strict';

	/**
	 * Initialize YouTube lazy loading
	 */
	function initYouTubeLazyLoad() {
		const youtubeContainers = document.querySelectorAll('.webtero-youtube');

		youtubeContainers.forEach(container => {
			const playButton = container.querySelector('.webtero-youtube__play-button');
			if (!playButton) return;

			// Check if already initialized
			if (playButton.dataset.initialized) return;
			playButton.dataset.initialized = 'true';

			playButton.addEventListener('click', function(e) {
				e.preventDefault();

				const embedUrl = this.dataset.embedUrl;
				if (!embedUrl) return;

				// Get containers
				const thumbnailContainer = container.querySelector('.webtero-youtube__thumbnail');
				const iframeContainer = container.querySelector('.webtero-youtube__iframe-container');

				// Create iframe
				const iframe = document.createElement('iframe');
				iframe.setAttribute('src', embedUrl);
				iframe.setAttribute('frameborder', '0');
				iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share');
				iframe.setAttribute('allowfullscreen', '');
				iframe.setAttribute('title', 'YouTube video player');
				iframe.className = 'webtero-youtube__iframe';

				// Hide thumbnail, show iframe
				if (thumbnailContainer) {
					thumbnailContainer.style.display = 'none';
				}

				if (iframeContainer) {
					iframeContainer.style.display = 'block';
					iframeContainer.appendChild(iframe);
				}
			});
		});
	}

	// Initialize on DOM ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initYouTubeLazyLoad);
	} else {
		initYouTubeLazyLoad();
	}

	// Re-initialize when new content is loaded (for dynamic content/AJAX)
	if (typeof MutationObserver !== 'undefined') {
		const observer = new MutationObserver(function(mutations) {
			let shouldReinit = false;
			mutations.forEach(function(mutation) {
				if (mutation.addedNodes.length > 0) {
					mutation.addedNodes.forEach(function(node) {
						if (node.nodeType === 1 && (
							node.classList && node.classList.contains('webtero-youtube') ||
							node.querySelector && node.querySelector('.webtero-youtube')
						)) {
							shouldReinit = true;
						}
					});
				}
			});
			if (shouldReinit) {
				initYouTubeLazyLoad();
			}
		});

		observer.observe(document.body, {
			childList: true,
			subtree: true
		});
	}
})();
