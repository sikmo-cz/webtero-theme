import { helpers } from './theme/helpers.js';

window.theme = {
	globals: {
		isTouch: typeof window !== 'undefined' && 'ontouchstart' in window,
	},

	init: function () {
		var self = this;
	},
};

document.addEventListener('DOMContentLoaded', function() {
	theme.init();
});