/* Quote form: send via AJAX; pre-tick an interest from ?interest=… in the URL. */
(function () {
	'use strict';
	var cfg = window.dluxQuote || {};

	document.querySelectorAll('[data-quote-form]').forEach(function (form) {
		// Pre-select from the URL, e.g. /beyond-detailing/?interest=Wraps#quote
		try {
			var want = new URLSearchParams(window.location.search).getAll('interest').map(function (v) { return v.toLowerCase(); });
			form.querySelectorAll('input[name="dlux_interests[]"]').forEach(function (box) {
				if (want.indexOf(box.value.toLowerCase()) !== -1) box.checked = true;
			});
		} catch (e) {}

		if (!cfg.ajaxUrl || !window.fetch || !window.FormData) return; // normal POST fallback

		var notice = form.parentNode.querySelector('[data-quote-notice]');
		var button = form.querySelector('[data-quote-submit]');
		var label = button ? button.querySelector('span') : null;
		var original = label ? label.textContent : '';

		function show(ok, text) {
			if (!notice) return;
			notice.className = 'quote__notice ' + (ok ? 'is-success' : 'is-error');
			notice.textContent = text;
			notice.hidden = false;
			notice.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
		}
		function busy(on) {
			if (!button) return;
			button.disabled = on;
			if (label) label.textContent = on ? (cfg.sending || 'Sending…') : original;
		}

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			if (form.checkValidity && !form.checkValidity()) { form.reportValidity(); return; }
			var phone = (form.elements.dlux_phone && form.elements.dlux_phone.value || '').replace(/\D/g, '');
			var email = form.elements.dlux_email && form.elements.dlux_email.value;
			if (!email && phone.length < 8) { show(false, cfg.invalid || 'Please add a mobile number or email.'); return; }

			var data = new FormData(form);
			data.set('action', 'dlux_quote');
			busy(true);
			fetch(cfg.ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' })
				.then(function (res) { return res.json().catch(function () { return { success: false, data: {} }; }); })
				.then(function (json) {
					var msg = json && json.data && json.data.message;
					if (json && json.success) { show(true, msg || cfg.success); form.reset(); }
					else { show(false, msg || cfg.error); }
				})
				.catch(function () { show(false, cfg.error); })
				.then(function () { busy(false); });
		});
	});
})();
