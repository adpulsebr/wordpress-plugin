/**
 * AdPulse sGTM - Admin Scripts
 *
 * @package AdPulse_sGTM
 */

(function() {
	'use strict';

	/**
	 * AdPulse Admin Class
	 */
	var AdPulseAdmin = {
		/**
		 * Initialize
		 */
		init: function() {
			this.bindEvents();
			this.validateContainerId();
		},

		/**
		 * Bind events
		 */
		bindEvents: function() {
			var containerIdInput = document.querySelector('input[name="adpulse_settings[sgtm][container_id]"]');
			var proxyPathInput = document.querySelector('input[name="adpulse_settings[sgtm][proxy_path]"]');

			if (containerIdInput) {
				containerIdInput.addEventListener('input', this.validateContainerId.bind(this));
				containerIdInput.addEventListener('blur', this.formatContainerId.bind(this));
			}

			if (proxyPathInput) {
				proxyPathInput.addEventListener('blur', this.formatProxyPath.bind(this));
			}
		},

		/**
		 * Validate container ID
		 */
		validateContainerId: function() {
			var input = document.querySelector('input[name="adpulse_settings[sgtm][container_id]"]');
			if (!input) return;

			var value = input.value;
			var isValid = /^\d*$/.test(value);

			if (value && !isValid) {
				// Remove non-numeric characters
				input.value = value.replace(/\D/g, '');
				this.showNotice('Container ID must be numeric only', 'warning');
			}
		},

		/**
		 * Format container ID
		 */
		formatContainerId: function() {
			var input = document.querySelector('input[name="adpulse_settings[sgtm][container_id]"]');
			if (!input) return;

			var value = input.value.trim();
			input.value = value;
		},

		/**
		 * Format proxy path
		 */
		formatProxyPath: function() {
			var input = document.querySelector('input[name="adpulse_settings[sgtm][proxy_path]"]');
			if (!input) return;

			var value = input.value.trim();
			value = '/' + value.replace(/^\/+|\/+$/g, '') + '/';

			// Ensure it's not just "//"
			if (value === '//') {
				value = '/c/';
			}

			input.value = value;
		},

		/**
		 * Show notice
		 *
		 * @param {string} message Notice message.
		 * @param {string} type Notice type.
		 */
		showNotice: function(message, type) {
			type = type || 'info';

			var notice = document.createElement('div');
			notice.className = 'notice notice-' + type + ' is-dismissible';
			notice.innerHTML = '<p>' + this.escapeHtml(message) + '</p>';

			var wrap = document.querySelector('.wrap');
			if (wrap) {
				wrap.insertBefore(notice, wrap.firstChild);

				// Auto-dismiss after 5 seconds
				setTimeout(function() {
					if (notice && notice.parentNode) {
						notice.parentNode.removeChild(notice);
					}
				}, 5000);
			}
		},

		/**
		 * Escape HTML
		 *
		 * @param {string} text Text to escape.
		 * @return {string} Escaped text.
		 */
		escapeHtml: function(text) {
			var div = document.createElement('div');
			div.textContent = text;
			return div.innerHTML;
		}
	};

	// Initialize when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function() {
			AdPulseAdmin.init();
		});
	} else {
		AdPulseAdmin.init();
	}

	// Expose to global scope for potential external use
	window.AdPulseAdmin = AdPulseAdmin;

})();
