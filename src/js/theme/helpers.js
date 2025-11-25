const helpers = {
	windowHeight: window.innerHeight,

	setCookie(name, value, days) 
	{
		let expires = '';

		if (days) {
			const date = new Date();
			date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
			expires = '; expires=' + date.toUTCString();
		}

		document.cookie = `${name}=${value || ''}${expires}; path=/`;
	},

	getCookie(name) 
	{
		const nameEQ = `${name}=`;
		const cookies = document.cookie.split(';');

		for (let cookie of cookies) {
			cookie = cookie.trim();
			if (cookie.indexOf(nameEQ) === 0) {
				return cookie.substring(nameEQ.length);
			}
		}
		return null;
	},

	eraseCookie(name) 
	{
		document.cookie = `${name}=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;`;
	},

	isOnScreen(element) 
	{
		const rect = element.getBoundingClientRect();
		const viewHeight = window.innerHeight || document.documentElement.clientHeight;
		const viewWidth = window.innerWidth || document.documentElement.clientWidth;

		return (
			rect.top < viewHeight &&
			rect.bottom > 0 &&
			rect.left < viewWidth &&
			rect.right > 0
		);
	},

	/**
	 * HOW TO USE:
	 * 
	 * helpers.apiCall({
	 *   action: 'ACTION_NAME',
	 *   data_name: 'data_value'
	 * }).then(response => {
	 *   console.log(response);
	 * }).catch(console.error);
	 * 
	 * // or send JSON:
	 * helpers.apiCall({ foo: 'bar' }, true)
	 */

	apiCall(data, asJson = false) 
	{
		let headers = {};
		let body;

		if (data instanceof FormData) {
			body = data; // browser sets the correct multipart headers
		} else if (asJson) {
			headers['Content-Type'] = 'application/json';
			body = JSON.stringify(data);
		} else {
			headers['Content-Type'] = 'application/x-www-form-urlencoded; charset=UTF-8';
			body = new URLSearchParams(data);
		}

		return fetch(sikmoVars.ajax_url, {
			method: 'POST',
			headers,
			body,
			cache: 'no-cache',
		}).then(response => {
			if (!response.ok) {
				throw new Error(`HTTP error! Status: ${response.status}`);
			}
			return response.json();
		}).catch(error => {
			console.error('API Error:', error);
			throw error;
		});
	},
};

export { helpers };