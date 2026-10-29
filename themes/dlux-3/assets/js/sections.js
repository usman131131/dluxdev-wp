/* Detailing packages (size toggle + "What's included" panel) and Info tabs. */
(function () {
	'use strict';

	function money(n) {
		n = Number(n);
		return '$' + (n % 1 === 0 ? n.toLocaleString('en-AU') : n.toLocaleString('en-AU', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
	}

	/* ---------- Detailing packages ----------
	 * Cards may be duplicated by the carousel (infinite loop), so everything goes by the
	 * card's index (data-dpk-card) and clicks are delegated from the section. */
	document.querySelectorAll('[data-dpk]').forEach(function (root) {
		var panels = Array.prototype.slice.call(root.querySelectorAll('[data-dpk-panel]'));
		var sizes = Array.prototype.slice.call(root.querySelectorAll('[data-dpk-size]'));
		var size = sizes.length ? sizes[0].getAttribute('data-dpk-size') : '';

		function cards() { return Array.prototype.slice.call(root.querySelectorAll('[data-dpk-card]')); }
		function panelFor(idx) { return root.querySelector('[data-dpk-panel="' + idx + '"]'); }
		function priceText(card) {
			var prices = {};
			try { prices = JSON.parse(card.getAttribute('data-prices') || '{}'); } catch (e) {}
			return Object.prototype.hasOwnProperty.call(prices, size) ? money(prices[size]) : 'Price on request';
		}

		function render() {
			var done = {};
			cards().forEach(function (card) {
				var text = priceText(card);
				var p = card.querySelector('[data-dpk-price]');
				if (p) p.textContent = text;
				var idx = card.getAttribute('data-dpk-card');
				if (done[idx]) return;
				done[idx] = true;
				var panel = panelFor(idx);
				if (!panel) return;
				var pp = panel.querySelector('[data-dpk-price]');
				if (pp) pp.textContent = text;
				var sl = panel.querySelector('[data-dpk-size-label]');
				if (sl) sl.textContent = size;
				var book = panel.querySelector('[data-dpk-book]');
				if (book && size) {
					try {
						var url = new URL(book.href, window.location.href);
						url.searchParams.set('size', size);
						book.href = url.toString();
					} catch (e) {}
				}
			});
		}

		function open(idx) {
			cards().forEach(function (c) { c.setAttribute('aria-expanded', c.getAttribute('data-dpk-card') === idx ? 'true' : 'false'); });
			panels.forEach(function (p) { p.hidden = p.getAttribute('data-dpk-panel') !== idx; });
			var panel = panelFor(idx);
			if (panel) {
				var r = panel.getBoundingClientRect();
				if (r.top > window.innerHeight - 120 || r.top < 0) {
					panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
				}
			}
		}

		root.addEventListener('click', function (e) {
			var sizeBtn = e.target.closest('[data-dpk-size]');
			var card = e.target.closest('[data-dpk-card]');
			if (sizeBtn) {
				size = sizeBtn.getAttribute('data-dpk-size');
				sizes.forEach(function (b) { b.setAttribute('aria-checked', b === sizeBtn ? 'true' : 'false'); });
				render();
			} else if (card) {
				open(card.getAttribute('data-dpk-card'));
			}
		});

		// The carousel adds copies of the cards after load: give them the current prices / state.
		root.addEventListener('dlux:dpk-cloned', function () {
			render();
			var on = root.querySelector('[data-dpk-card][aria-expanded="true"]');
			if (on) open(on.getAttribute('data-dpk-card'));
		});

		render();
	});

	/* ---------- Pick a package + add-ons, then book from the bottom bar ----------
	 * Detailing packages (card + size toggle) and Add-ons grid cards share one selection.
	 * The bar links to the booking page with ?service&package&size&extras preselected. */
	(function () {
		var bar = document.querySelector('[data-pick-bar]');
		if (!bar) return;
		var addons = Array.prototype.slice.call(document.querySelectorAll('[data-addon]'));
		var S = { service: '', pkg: '', name: '', prices: {}, size: '', extras: [] }; // extras: addon elements

		function parse(j) { try { return JSON.parse(j || '{}'); } catch (e) { return {}; } }
		function sizeOf(root) {
			var on = root.querySelector('[data-dpk-size][aria-checked="true"]');
			return on ? on.getAttribute('data-dpk-size') : '';
		}
		function idFor(a) {
			var ids = parse(a.getAttribute('data-extra-ids'));
			return ids['*'] || (S.pkg ? ids[S.pkg] : '') || '';
		}
		function offered(a) {
			var ids = parse(a.getAttribute('data-extra-ids'));
			return !S.pkg || !!ids['*'] || !!ids[S.pkg];
		}

		function render() {
			addons.forEach(function (a) {
				var ok = offered(a);
				var i = S.extras.indexOf(a);
				if (!ok && i >= 0) S.extras.splice(i, 1);
				var on = S.extras.indexOf(a) >= 0;
				a.classList.toggle('is-active', on);
				a.classList.toggle('is-unavailable', !ok);
				a.setAttribute('aria-pressed', on ? 'true' : 'false');
				a.disabled = !ok;
			});

			if (!S.pkg && !S.extras.length) { bar.hidden = true; document.body.classList.remove('has-pick-bar'); return; }

			var price = S.pkg && Object.prototype.hasOwnProperty.call(S.prices, S.size) ? Number(S.prices[S.size]) : null;
			var total = (price || 0), names = [], ids = [];
			S.extras.forEach(function (a) {
				names.push(a.getAttribute('data-extra-name'));
				total += Number(a.getAttribute('data-extra-price')) || 0;
				var id = idFor(a); if (id) ids.push(id);
			});

			var title = bar.querySelector('[data-pb-title]');
			var extras = bar.querySelector('[data-pb-extras]');
			var link = bar.querySelector('[data-pb-link]');
			var blocked = '';
			if (!S.pkg) blocked = 'Choose a package above to book these add-ons.';
			else if (price === null) blocked = S.name + ' isn\u2019t offered for ' + (S.size || 'this size') + '. Pick another size.';

			title.textContent = S.pkg ? S.name + (S.size ? ' \u00b7 ' + S.size : '') : 'Add-ons';
			extras.textContent = blocked || (names.length ? '+ ' + names.join(', ') : '');
			bar.classList.toggle('is-blocked', !!blocked);
			bar.querySelector('[data-pb-total]').textContent = money(total);

			var url = new URL(bar.getAttribute('data-book-url'), location.href);
			if (S.pkg) {
				url.searchParams.set('service', S.service || 'detail');
				url.searchParams.set('package', S.pkg);
				if (S.size) url.searchParams.set('size', S.size);
			}
			if (ids.length) url.searchParams.set('extras', ids.join(','));
			link.href = url.toString();
			link.classList.toggle('is-disabled', !!blocked);
			link.setAttribute('aria-disabled', blocked ? 'true' : 'false');
			bar.hidden = false;
			document.body.classList.add('has-pick-bar');
		}

		document.addEventListener('click', function (e) {
			var card = e.target.closest('[data-dpk-card]');
			var size = e.target.closest('[data-dpk-size]');
			var addon = e.target.closest('[data-addon]');
			if (card) {
				var root = card.closest('[data-dpk]');
				S.service = root ? root.getAttribute('data-service') : 'detail';
				S.pkg = card.getAttribute('data-package-id') || '';
				S.name = card.getAttribute('data-package-name') || '';
				S.prices = parse(card.getAttribute('data-prices'));
				S.size = (root && sizeOf(root)) || Object.keys(S.prices)[0] || '';
				render();
			} else if (size) {
				S.size = size.getAttribute('data-dpk-size');
				if (S.pkg || S.extras.length) render();
			} else if (addon && !addon.disabled) {
				var i = S.extras.indexOf(addon);
				if (i >= 0) S.extras.splice(i, 1); else S.extras.push(addon);
				render();
			}
		});

		bar.addEventListener('click', function (e) {
			var link = e.target.closest('[data-pb-link]');
			if (link && link.classList.contains('is-disabled')) {
				e.preventDefault();
				var target = document.querySelector('[data-dpk]');
				if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
			}
			if (e.target.closest('[data-pb-clear]')) {
				S.pkg = ''; S.name = ''; S.prices = {}; S.extras = [];
				render();
			}
		});
	})();

	/* ---------- Info tabs ---------- */
	document.querySelectorAll('[data-info-tabs]').forEach(function (root) {
		var tabs = Array.prototype.slice.call(root.querySelectorAll('[role="tab"]'));
		function select(tab, focus) {
			tabs.forEach(function (t) {
				var on = t === tab;
				t.setAttribute('aria-selected', on ? 'true' : 'false');
				t.tabIndex = on ? 0 : -1;
				var id = t.getAttribute('aria-controls');
				var panel = document.getElementById(id);
				if (panel) panel.hidden = !on;
				root.querySelectorAll('[data-it-for="' + id + '"]').forEach(function (h) { h.hidden = !on; });
			});
			if (focus) tab.focus();
		}
		tabs.forEach(function (tab, i) {
			tab.addEventListener('click', function () { select(tab); });
			tab.addEventListener('keydown', function (e) {
				var n = e.key === 'ArrowRight' ? 1 : e.key === 'ArrowLeft' ? -1 : 0;
				if (!n) return;
				e.preventDefault();
				select(tabs[(i + n + tabs.length) % tabs.length], true);
			});
		});
	});
})();
