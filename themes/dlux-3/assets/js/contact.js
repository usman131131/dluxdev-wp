/* Contact Us: send the theme's own form via AJAX (admin-ajax.php). */
(function () {
	'use strict';
	var cfg = window.dluxContact || {};
	if (!cfg.ajaxUrl || !window.fetch || !window.FormData) return; // falls back to a normal POST

	document.querySelectorAll('[data-contact-form]').forEach(function (form) {
		var wrap = form.closest('.contact__form-wrap');
		var notice = wrap ? wrap.querySelector('[data-contact-notice]') : null;
		var button = form.querySelector('[data-contact-submit]');
		var label = button ? button.querySelector('span') : null;
		var original = label ? label.textContent : '';

		function show(type, text) {
			if (!notice) return;
			notice.className = 'contact__notice ' + (type === 'success' ? 'is-success' : 'is-error');
			notice.textContent = text;
			notice.hidden = false;
		}

		function busy(on) {
			if (!button) return;
			button.disabled = on;
			button.classList.toggle('is-loading', on);
			if (label) label.textContent = on ? (cfg.sending || 'Sending…') : original;
		}

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			if (form.checkValidity && !form.checkValidity()) { form.reportValidity(); return; }

			var data = new FormData(form);
			data.set('action', 'dlux_contact');
			busy(true);

			fetch(cfg.ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' })
				.then(function (res) { return res.json().catch(function () { return { success: false, data: {} }; }); })
				.then(function (json) {
					var msg = json && json.data && json.data.message;
					if (json && json.success) {
						show('success', msg || cfg.success);
						form.reset();
					} else {
						show('error', msg || cfg.error);
					}
				})
				.catch(function () { show('error', cfg.error); })
				.then(function () { busy(false); });
		});
	});
})();
