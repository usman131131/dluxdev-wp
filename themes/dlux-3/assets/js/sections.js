/* Detailing packages (size toggle + "What's included" panel) and Info tabs. */
(function () {
	'use strict';

	function money(n) {
		n = Number(n);
		return '$' + (n % 1 === 0 ? n.toLocaleString('en-AU') : n.toLocaleString('en-AU', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
	}

	/* ---------- Detailing packages ---------- */
	document.querySelectorAll('[data-dpk]').forEach(function (root) {
		var cards = Array.prototype.slice.call(root.querySelectorAll('[data-dpk-card]'));
		var panels = Array.prototype.slice.call(root.querySelectorAll('[data-dpk-panel]'));
		var sizes = Array.prototype.slice.call(root.querySelectorAll('[data-dpk-size]'));
		var size = sizes.length ? sizes[0].getAttribute('data-dpk-size') : '';

		function priceText(card) {
			var prices = {};
			try { prices = JSON.parse(card.getAttribute('data-prices') || '{}'); } catch (e) {}
			return Object.prototype.hasOwnProperty.call(prices, size) ? money(prices[size]) : 'Price on request';
		}

		function render() {
			cards.forEach(function (card, i) {
				var text = priceText(card);
				var p = card.querySelector('[data-dpk-price]');
				if (p) p.textContent = text;
				var panel = panels[i];
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

		sizes.forEach(function (btn) {
			btn.addEventListener('click', function () {
				size = btn.getAttribute('data-dpk-size');
				sizes.forEach(function (b) { b.setAttribute('aria-checked', b === btn ? 'true' : 'false'); });
				render();
			});
		});

		cards.forEach(function (card, i) {
			card.addEventListener('click', function () {
				cards.forEach(function (c, j) { c.setAttribute('aria-expanded', j === i ? 'true' : 'false'); });
				panels.forEach(function (p, j) { p.hidden = j !== i; });
				var panel = panels[i];
				if (panel) {
					var r = panel.getBoundingClientRect();
					if (r.top > window.innerHeight - 120 || r.top < 0) {
						panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
					}
				}
			});
		});

		render();
	});

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
