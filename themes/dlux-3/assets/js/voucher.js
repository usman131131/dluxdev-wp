/* Gift voucher popup: open from the voucher card, send the sign-up form via AJAX. */
(function () {
	'use strict';
	var modal = document.querySelector('[data-voucher-modal]');
	if (!modal) return;
	var cfg = window.dluxVoucher || {};
	var box = modal.querySelector('.voucher-modal__box');
	var lastFocus = null;

	function open(e) {
		if (e) e.preventDefault();
		lastFocus = document.activeElement;
		modal.hidden = false;
		document.body.classList.add('voucher-open');
		requestAnimationFrame(function () { modal.classList.add('is-open'); });
		var first = modal.querySelector('input:not([type="hidden"]):not([tabindex="-1"])');
		(first || box).focus();
	}
	function close() {
		modal.classList.remove('is-open');
		document.body.classList.remove('voucher-open');
		setTimeout(function () { modal.hidden = true; }, 200);
		if (lastFocus && lastFocus.focus) lastFocus.focus();
	}

	// Voucher card (anywhere on it) and any link to #gift-voucher
	document.addEventListener('click', function (e) {
		var t = e.target.closest('[data-voucher-open], a[href$="#gift-voucher"]');
		if (t && !modal.contains(t)) open(e);
	});
	document.querySelectorAll('[data-voucher-open]').forEach(function (card) {
		card.setAttribute('role', 'button');
		card.setAttribute('tabindex', '0');
		card.addEventListener('keydown', function (e) {
			if ((e.key === 'Enter' || e.key === ' ') && e.target === card) open(e);
		});
	});
	modal.addEventListener('click', function (e) { if (e.target.closest('[data-voucher-close]')) close(); });
	document.addEventListener('keydown', function (e) {
		if (modal.hidden) return;
		if (e.key === 'Escape') close();
		if (e.key === 'Tab') { // keep focus inside the popup
			var f = Array.prototype.slice.call(box.querySelectorAll('button, input:not([type="hidden"]):not([tabindex="-1"]), a[href]'));
			if (!f.length) return;
			var a = f[0], z = f[f.length - 1];
			if (e.shiftKey && document.activeElement === a) { e.preventDefault(); z.focus(); }
			else if (!e.shiftKey && document.activeElement === z) { e.preventDefault(); a.focus(); }
		}
	});
	if (location.hash === '#gift-voucher') open();

	// Form
	var form = modal.querySelector('[data-voucher-form]');
	if (!form || !window.fetch) return;
	var notice = form.querySelector('[data-voucher-notice]');
	var btn = form.querySelector('[type="submit"]');
	var label = btn ? btn.querySelector('span') : null;
	var original = label ? label.textContent : '';

	function show(ok, text) {
		notice.textContent = text;
		notice.className = 'dlux-voucher__notice ' + (ok ? 'is-success' : 'is-error');
		notice.hidden = false;
	}
	form.addEventListener('submit', function (e) {
		e.preventDefault();
		if (!form.checkValidity()) { form.reportValidity(); return; }
		btn.disabled = true;
		if (label) label.textContent = cfg.sending || 'Sending…';
		fetch(form.getAttribute('action'), { method: 'POST', body: new FormData(form), credentials: 'same-origin' })
			.then(function (r) { return r.json().catch(function () { return {}; }); })
			.then(function (j) {
				var msg = j && j.data && j.data.message;
				if (j && j.success) { show(true, msg); form.reset(); } else { show(false, msg || cfg.error); }
			})
			.catch(function () { show(false, cfg.error); })
			.then(function () { btn.disabled = false; if (label) label.textContent = original; });
	});
})();
