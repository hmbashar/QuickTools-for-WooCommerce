(function () {
	'use strict';

	function syncCard(input) {
		var card = input.closest('.qtfw-feature-card');
		if (!card) {
			return;
		}

		var enabled = !!input.checked;
		card.classList.toggle('is-enabled', enabled);
		card.classList.toggle('is-disabled', !enabled);

		var statusText = card.querySelector('.qtfw-feature-card__status-text');
		if (!statusText) {
			return;
		}

		var onLabel = statusText.getAttribute('data-on-label') || 'Enabled';
		var offLabel = statusText.getAttribute('data-off-label') || 'Disabled';
		statusText.textContent = enabled ? onLabel : offLabel;
	}

	function init() {
		var root = document.querySelector('.qtfw-settings');
		if (!root) {
			return;
		}

		root.querySelectorAll('.qtfw-switch__input').forEach(function (input) {
			input.addEventListener('change', function () {
				syncCard(input);
			});
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
